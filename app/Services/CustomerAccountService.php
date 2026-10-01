<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Gives the person who booked a ride an account to log in with, the moment his
 * ride is confirmed, so he can follow it from his own panel.
 *
 * The account is looked up by the address the booking was made with, so a
 * customer who books again finds his earlier account and is never sent a second
 * copy of his credentials. An address that already belongs to staff is left
 * alone: a staff account must never be turned into a customer, and the unique
 * index on users.email would refuse the insert anyway.
 */
class CustomerAccountService
{
    public function __construct(
        protected MailNotificationService $mailNotifications,
    ) {}

    /**
     * Find or create the customer account for this confirmed booking, link it to
     * the ride, and email him his login details when the account is brand new.
     *
     * A failure here must never cost the customer his ride, so it is logged and
     * swallowed exactly like a mail delivery failure.
     *
     * @return User|null the customer account, or null when the ride cannot have one
     */
    public function welcomeConfirmedCustomer(Booking $booking): ?User
    {
        try {
            return $this->provision($booking);
        } catch (Throwable $exception) {
            Log::error('Customer account could not be created for a confirmed booking', [
                'booking_id' => $booking->id,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function provision(Booking $booking): ?User
    {
        $email = Str::lower(trim((string) $booking->customer_email));

        if ($email === '') {
            // With no address there is nowhere to send the credentials, and so
            // no account this customer could ever log in with.
            return null;
        }

        $user = User::where('email', $email)->first();

        if ($user !== null && ! $user->isCustomer()) {
            Log::error('Customer account not created: the booking address belongs to a staff account', [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'role' => $user->role,
            ]);

            return null;
        }

        $plainPassword = null;

        if ($user === null) {
            $plainPassword = self::generatePassword();

            $user = new User([
                'name' => $booking->customer_name,
                'email' => $email,
                // The hashed cast turns this into a hash on the way in.
                'password' => $plainPassword,
                'role' => User::ROLE_CUSTOMER,
                'status' => User::STATUS_ACTIVE,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $booking->update(['customer_user_id' => $user->id]);

        // Credentials go out only for a brand new account: a second ride booked
        // from the same address must not mail him his password again.
        if ($plainPassword !== null) {
            $this->mailNotifications->notifyCustomerLoginDetails($booking, $user, $plainPassword);
        }

        return $user;
    }

    /**
     * A password the customer can read off the screen and type on his phone:
     * letters and digits only, with nothing that is easily confused.
     */
    protected static function generatePassword(): string
    {
        return Str::password(length: 12, letters: true, numbers: true, symbols: false, spaces: false);
    }
}

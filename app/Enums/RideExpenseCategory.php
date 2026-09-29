<?php

namespace App\Enums;

/**
 * What the driver paid for while the ride was running. Fuel is the common case
 * (the driver fills the tank on the way and gets the money back), the Other
 * bucket covers parking, tolls and similar out-of-pocket costs.
 */
enum RideExpenseCategory: string
{
    case Petrol = 'Petrol';
    case Diesel = 'Diesel';
    case Gas = 'Gas';
    case Other = 'Other';

    /**
     * The text shown next to an expense in the driver and admin screens.
     */
    public function label(): string
    {
        return match ($this) {
            self::Petrol => 'Petrol Money',
            self::Diesel => 'Diesel Money',
            self::Gas => 'Gas Money',
            self::Other => 'Other Expense',
        };
    }
}

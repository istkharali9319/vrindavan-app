<?php

namespace App\Enums;

/**
 * Roles from ARCHITECTURE.md §9. Every role except Customer is staff and may open /admin.
 */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Manager = 'manager';
    case FrontDesk = 'front_desk';
    case Accounts = 'accounts';
    case ContentEditor = 'content_editor';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super admin',
            self::Manager => 'Manager',
            self::FrontDesk => 'Front desk',
            self::Accounts => 'Accounts',
            self::ContentEditor => 'Content editor',
            self::Customer => 'Customer',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }

    /**
     * @return list<self>
     */
    public static function staff(): array
    {
        return array_values(array_filter(self::cases(), fn (self $role): bool => $role->isStaff()));
    }

    /**
     * @return list<string>
     */
    public static function staffValues(): array
    {
        return array_map(fn (self $role): string => $role->value, self::staff());
    }

    /**
     * Permissions granted to the role. Super admin is granted everything through a gate bypass.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => Permission::cases(),
            self::Manager => [
                Permission::DashboardView,
                Permission::UsersView, Permission::UsersManage,
                Permission::CatalogManage,
                Permission::InventoryView, Permission::InventoryManage,
                Permission::PricingManage, Permission::CouponsManage,
                Permission::BookingsView, Permission::BookingsManage,
                Permission::PaymentsView,
                Permission::RefundsManage,
                Permission::ReportsView,
                Permission::ContentManage,
                Permission::EnquiriesManage,
            ],
            self::FrontDesk => [
                Permission::DashboardView,
                Permission::UsersView,
                Permission::InventoryView, Permission::InventoryManage,
                Permission::BookingsView, Permission::BookingsManage,
                Permission::PaymentsView,
                Permission::EnquiriesManage,
            ],
            self::Accounts => [
                Permission::DashboardView,
                Permission::UsersView,
                Permission::InventoryView,
                Permission::BookingsView,
                Permission::PaymentsView, Permission::PaymentsManage,
                Permission::RefundsManage, Permission::RefundsOverride,
                Permission::ReportsView,
            ],
            self::ContentEditor => [
                Permission::DashboardView,
                Permission::ContentManage,
            ],
            self::Customer => [],
        };
    }
}

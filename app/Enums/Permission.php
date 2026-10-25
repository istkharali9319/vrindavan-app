<?php

namespace App\Enums;

/**
 * Admin permissions from ARCHITECTURE.md §9.
 */
enum Permission: string
{
    case DashboardView = 'dashboard.view';
    case UsersView = 'users.view';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';
    case CatalogManage = 'catalog.manage';
    case InventoryView = 'inventory.view';
    case InventoryManage = 'inventory.manage';
    case PricingManage = 'pricing.manage';
    case CouponsManage = 'coupons.manage';
    case BookingsView = 'bookings.view';
    case BookingsManage = 'bookings.manage';
    case PaymentsView = 'payments.view';
    case PaymentsManage = 'payments.manage';
    case RefundsManage = 'refunds.manage';
    case RefundsOverride = 'refunds.override';
    case ReportsView = 'reports.view';
    case ContentManage = 'content.manage';
    case EnquiriesManage = 'enquiries.manage';
    case SettingsManage = 'settings.manage';
    case AuditView = 'audit.view';
}

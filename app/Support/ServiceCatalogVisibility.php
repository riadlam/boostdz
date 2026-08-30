<?php

namespace App\Support;

use App\Models\User;

class ServiceCatalogVisibility
{
    /**
     * @return list<string>
     */
    public static function viewerEmails(): array
    {
        $emails = config('catalog.service_catalog_viewer_emails', []);

        return array_values(array_filter(array_map(
            static fn (string $email): string => strtolower(trim($email)),
            is_array($emails) ? $emails : [],
        )));
    }

    public static function canView(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $email = strtolower(trim((string) $user->email));

        return $email !== '' && in_array($email, self::viewerEmails(), true);
    }
}

<?php

namespace App\Filament\Concerns;

use App\Domain\Identity\AccountPasswords;

/**
 * For the Teacher and Parent create/edit pages: the form has a password field, but the password belongs to the
 * linked sign-in account, not to the profile, so it is taken out of the data before the profile is saved and
 * applied to the account afterwards.
 */
trait HandlesAccountPassword
{
    protected ?string $pendingPassword = null;

    /** The role the linked sign-in account must have: 'Teacher' or 'Parent'. */
    abstract protected function accountRole(): string;

    private function takePassword(array $data): array
    {
        $this->pendingPassword = filled($data['password'] ?? null) ? (string) $data['password'] : null;
        unset($data['password']);

        return $data;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->takePassword($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->takePassword($data);
    }

    protected function afterCreate(): void
    {
        AccountPasswords::apply($this->record, $this->pendingPassword, $this->accountRole());
    }

    protected function afterSave(): void
    {
        AccountPasswords::syncEmail($this->record);
        AccountPasswords::apply($this->record, $this->pendingPassword, $this->accountRole());
    }
}

<?php

namespace App\Filament\Resources\LinkResource\Pages;

use App\Filament\Resources\LinkResource;
use App\Services\ShortCodeGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateLink extends CreateRecord
{
    protected static string $resource = LinkResource::class;

    /**
     * Attach the owner and generate a unique short code before persisting.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['code'] = app(ShortCodeGenerator::class)->generateUnique();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

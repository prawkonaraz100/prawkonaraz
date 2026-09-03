<?php

namespace App\Filament\Resources\ContentImportRuns\Schemas;

use App\Models\ContentImportRun;
use App\Support\QuestionIntegrityAuditService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ContentImportRunInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('created_at')
                    ->label('Utworzono')
                    ->dateTime(),
                TextEntry::make('kind')
                    ->label('Typ runu')
                    ->badge()
                    ->color('gray'),
                TextEntry::make('identifier')
                    ->label('Identyfikator')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ok' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextEntry::make('dry_run')
                    ->label('Dry run')
                    ->formatStateUsing(static fn (bool $state): string => $state ? 'tak' : 'nie'),
                TextEntry::make('source_path')
                    ->label('Ścieżka źródła')
                    ->placeholder('-')
                    ->wrap()
                    ->columnSpanFull(),
                TextEntry::make('output_path')
                    ->label('Katalog wyjściowy')
                    ->placeholder('-')
                    ->wrap()
                    ->columnSpanFull(),
                TextEntry::make('report_path')
                    ->label('Ścieżka raportu')
                    ->placeholder('-')
                    ->wrap()
                    ->columnSpanFull(),
                TextEntry::make('started_at')
                    ->label('Start')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('completed_at')
                    ->label('Koniec')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('rows_total')
                    ->label('Wiersze')
                    ->numeric(),
                TextEntry::make('questions_total')
                    ->label('Pytania')
                    ->numeric(),
                TextEntry::make('media_total')
                    ->label('Media')
                    ->numeric(),
                TextEntry::make('asset_plan_total')
                    ->label('Plan assetów')
                    ->numeric(),
                TextEntry::make('uploaded_assets_total')
                    ->label('Wgrane assety')
                    ->numeric(),
                TextEntry::make('warnings_count')
                    ->label('Ostrzeżenia')
                    ->numeric(),
                TextEntry::make('errors_count')
                    ->label('Błędy')
                    ->numeric(),
                TextEntry::make('integrity_audit_status')
                    ->label('Integralność pytań')
                    ->state(function (ContentImportRun $record): string {
                        $status = app(QuestionIntegrityAuditService::class)->resolveStatusMeta(
                            is_array($record->summary['integrity_audit'] ?? null)
                                ? $record->summary['integrity_audit']
                                : null,
                        );

                        return $status['label'].' - '.$status['description'];
                    })
                    ->badge()
                    ->color(function (ContentImportRun $record): string {
                        $status = app(QuestionIntegrityAuditService::class)->resolveStatusMeta(
                            is_array($record->summary['integrity_audit'] ?? null)
                                ? $record->summary['integrity_audit']
                                : null,
                        );

                        return $status['color'];
                    })
                    ->placeholder('Brak danych'),
                TextEntry::make('summary')
                    ->label('Podsumowanie')
                    ->placeholder('{}')
                    ->formatStateUsing(function (mixed $state): string {
                        if (! is_array($state) || $state === []) {
                            return '{}';
                        }

                        return json_encode(
                            $state,
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        ) ?: '{}';
                    })
                    ->columnSpanFull(),
            ]);
    }
}

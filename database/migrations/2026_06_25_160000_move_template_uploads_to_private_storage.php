<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Verplaats logo's en achtergronden van public naar private storage.
     * Paden in de database worden bijgewerkt van 'logos/xxx' naar 'template-files/logos/xxx'.
     */
    public function up(): void
    {
        $templates = DB::table('invoice_templates')
            ->whereNotNull('logo_path')
            ->orWhereNotNull('background_path')
            ->get(['id', 'logo_path', 'background_path']);

        foreach ($templates as $template) {
            // Logo verplaatsen
            if ($template->logo_path) {
                $oldPath = $template->logo_path; // bijv. 'logos/abc.jpg'
                $newPath = 'template-files/' . $oldPath; // bijv. 'template-files/logos/abc.jpg'

                // Bestand verplaatsen van public naar private disk
                $publicFile = storage_path('app/public/' . $oldPath);
                if (file_exists($publicFile)) {
                    Storage::makeDirectory('template-files/logos');
                    Storage::put($newPath, file_get_contents($publicFile));
                    @unlink($publicFile);
                }

                DB::table('invoice_templates')
                    ->where('id', $template->id)
                    ->update(['logo_path' => $newPath]);
            }

            // Achtergrond verplaatsen
            if ($template->background_path) {
                $oldPath = $template->background_path;
                $newPath = 'template-files/' . $oldPath;

                $publicFile = storage_path('app/public/' . $oldPath);
                if (file_exists($publicFile)) {
                    Storage::makeDirectory('template-files/backgrounds');
                    Storage::put($newPath, file_get_contents($publicFile));
                    @unlink($publicFile);
                }

                DB::table('invoice_templates')
                    ->where('id', $template->id)
                    ->update(['background_path' => $newPath]);
            }
        }
    }

    /**
     * Terugdraaien: bestanden terug naar public disk.
     */
    public function down(): void
    {
        $templates = DB::table('invoice_templates')
            ->whereNotNull('logo_path')
            ->orWhereNotNull('background_path')
            ->get(['id', 'logo_path', 'background_path']);

        foreach ($templates as $template) {
            if ($template->logo_path && str_starts_with($template->logo_path, 'template-files/')) {
                $newPath = $template->logo_path;
                $oldPath = str_replace('template-files/', '', $newPath);

                if (Storage::exists($newPath)) {
                    $publicDir = storage_path('app/public/' . dirname($oldPath));
                    if (! is_dir($publicDir)) mkdir($publicDir, 0755, true);
                    file_put_contents(
                        storage_path('app/public/' . $oldPath),
                        Storage::get($newPath)
                    );
                    Storage::delete($newPath);
                }

                DB::table('invoice_templates')
                    ->where('id', $template->id)
                    ->update(['logo_path' => $oldPath]);
            }

            if ($template->background_path && str_starts_with($template->background_path, 'template-files/')) {
                $newPath = $template->background_path;
                $oldPath = str_replace('template-files/', '', $newPath);

                if (Storage::exists($newPath)) {
                    $publicDir = storage_path('app/public/' . dirname($oldPath));
                    if (! is_dir($publicDir)) mkdir($publicDir, 0755, true);
                    file_put_contents(
                        storage_path('app/public/' . $oldPath),
                        Storage::get($newPath)
                    );
                    Storage::delete($newPath);
                }

                DB::table('invoice_templates')
                    ->where('id', $template->id)
                    ->update(['background_path' => $oldPath]);
            }
        }
    }
};

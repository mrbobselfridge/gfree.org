<?php

namespace App\Support;

use App\Models\SlideDeck;
use RuntimeException;
use Symfony\Component\Process\Process;

class PowerPointToPdfService
{
    public function convert(SlideDeck $deck, string $workDirectory): string
    {
        $source = storage_path('app/private/'.$deck->stored_file_path);

        if (! is_file($source)) {
            throw new RuntimeException('The uploaded PowerPoint file could not be found.');
        }

        $binary = $this->binary();

        if ($binary === null) {
            throw new RuntimeException('LibreOffice is not installed or is not available on the server PATH.');
        }

        if (! is_dir($workDirectory) && ! mkdir($workDirectory, 0755, true) && ! is_dir($workDirectory)) {
            throw new RuntimeException('Could not create the slide deck conversion workspace.');
        }

        $profile = $this->prepareLibreOfficeProfile($workDirectory);

        $process = new Process([
            $binary,
            '--headless',
            '-env:UserInstallation='.$this->fileUri($profile['user_installation']),
            '--convert-to',
            'pdf',
            '--outdir',
            $workDirectory,
            $source,
        ]);
        $process->setEnv([
            'HOME' => $profile['home'],
            'XDG_CACHE_HOME' => $profile['cache'],
            'XDG_CONFIG_HOME' => $profile['config'],
            'XDG_RUNTIME_DIR' => $profile['runtime'],
        ]);
        $process->setTimeout(180);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('LibreOffice could not convert the PowerPoint file: '.$this->cleanOutput($process));
        }

        $pdf = collect(glob($workDirectory.'/*.pdf') ?: [])->first();

        if (! is_string($pdf) || ! is_file($pdf)) {
            throw new RuntimeException('LibreOffice finished without creating a PDF.');
        }

        return $pdf;
    }

    private function binary(): ?string
    {
        foreach (['libreoffice', 'soffice'] as $binary) {
            $process = Process::fromShellCommandline('command -v '.escapeshellarg($binary));
            $process->run();

            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
        }

        return null;
    }

    /**
     * LibreOffice needs a writable per-user profile even in headless mode.
     */
    private function prepareLibreOfficeProfile(string $workDirectory): array
    {
        $base = $workDirectory.'/libreoffice';
        $paths = [
            'home' => $base.'/home',
            'cache' => $base.'/cache',
            'config' => $base.'/config',
            'runtime' => $base.'/runtime',
            'user_installation' => $base.'/profile',
        ];

        foreach ($paths as $path) {
            if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
                throw new RuntimeException('Could not create the LibreOffice profile workspace.');
            }
        }

        chmod($paths['runtime'], 0700);

        return $paths;
    }

    private function fileUri(string $path): string
    {
        return 'file://'.str_replace('%2F', '/', rawurlencode($path));
    }

    private function cleanOutput(Process $process): string
    {
        return trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'unknown conversion error';
    }
}

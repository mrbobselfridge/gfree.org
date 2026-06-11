<x-filament-panels::page>
    @php
        $profiles = $this->profiles();
    @endphp

    <style>
        .twyxtco-backups-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
            gap: 1rem;
        }

        .twyxtco-backup-card {
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            background: white;
            padding: 1rem;
        }

        .dark .twyxtco-backup-card {
            border-color: rgb(31 41 55);
            background: rgb(17 24 39);
        }

        .twyxtco-backup-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.625rem;
            margin-top: 0.875rem;
        }

        .twyxtco-backup-meta div {
            min-width: 0;
            border: 1px solid rgb(243 244 246);
            border-radius: 0.5rem;
            padding: 0.625rem;
        }

        .dark .twyxtco-backup-meta div {
            border-color: rgb(31 41 55);
        }

        .twyxtco-backup-meta dt {
            color: rgb(107 114 128);
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .twyxtco-backup-meta dd {
            overflow: hidden;
            margin: 0.125rem 0 0;
            color: rgb(17 24 39);
            font-size: 0.8125rem;
            font-weight: 650;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .twyxtco-backup-meta dd {
            color: white;
        }

        .twyxtco-backup-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .twyxtco-backup-button,
        .twyxtco-backup-link {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            min-height: 2rem;
            border-radius: 0.5rem;
            padding: 0.375rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .twyxtco-backup-button {
            background: rgb(22 163 74);
            color: white;
        }

        .twyxtco-backup-link {
            border: 1px solid rgb(209 213 219);
            color: rgb(55 65 81);
        }

        .twyxtco-backup-link--danger {
            border-color: rgb(248 113 113);
            color: rgb(185 28 28);
        }

        .dark .twyxtco-backup-link {
            border-color: rgb(55 65 81);
            color: rgb(229 231 235);
        }

        .dark .twyxtco-backup-link--danger {
            border-color: rgb(127 29 29);
            color: rgb(252 165 165);
        }

        .twyxtco-backup-button svg,
        .twyxtco-backup-link svg {
            width: 1rem;
            height: 1rem;
        }

        .twyxtco-backup-list {
            margin: 0.875rem 0 0;
            padding-left: 1rem;
            color: rgb(75 85 99);
            font-size: 0.8125rem;
        }

        .dark .twyxtco-backup-list {
            color: rgb(209 213 219);
        }

        .twyxtco-backup-list li + li {
            margin-top: 0.25rem;
        }

        .twyxtco-backup-table {
            width: 100%;
            margin-top: 0.875rem;
            border-collapse: collapse;
            font-size: 0.75rem;
        }

        .twyxtco-backup-table th,
        .twyxtco-backup-table td {
            border-top: 1px solid rgb(243 244 246);
            padding: 0.5rem 0.25rem;
            text-align: left;
            vertical-align: top;
        }

        .dark .twyxtco-backup-table th,
        .dark .twyxtco-backup-table td {
            border-color: rgb(31 41 55);
        }

        .twyxtco-backup-table th {
            color: rgb(107 114 128);
            font-weight: 700;
        }

        .twyxtco-backup-table td {
            color: rgb(55 65 81);
        }

        .dark .twyxtco-backup-table td {
            color: rgb(209 213 219);
        }

        @media (max-width: 640px) {
            .twyxtco-backup-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="space-y-6">
        <div class="twyxtco-backups-grid">
            @foreach ($profiles as $profile)
                @php
                    $latest = $profile['latest'];
                    $recentBackups = $profile['recent_backups'];
                @endphp

                <article class="twyxtco-backup-card">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-base font-semibold text-gray-950 dark:text-white">{{ $profile['label'] }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $profile['description'] }}</p>
                        </div>
                        <span class="shrink-0 rounded-md bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            {{ $profile['type'] }}
                        </span>
                    </div>

                    <dl class="twyxtco-backup-meta">
                        <div>
                            <dt>Schedule</dt>
                            <dd title="{{ $profile['schedule_label'] }}">{{ $profile['schedule_label'] }}</dd>
                        </div>
                        <div>
                            <dt>Destination</dt>
                            <dd title="{{ implode(', ', $profile['disks']) }}">{{ implode(', ', $profile['disks']) }}</dd>
                        </div>
                        <div>
                            <dt>Latest</dt>
                            <dd title="{{ $latest['timestamp'] ?? 'No backups yet' }}">{{ $latest ? $latest['age'] : 'No backups yet' }}</dd>
                        </div>
                        <div>
                            <dt>Size</dt>
                            <dd>{{ $latest['size_for_humans'] ?? 'Pending' }}</dd>
                        </div>
                    </dl>

                    <div class="twyxtco-backup-actions">
                        <button
                            type="button"
                            class="twyxtco-backup-button"
                            wire:click="mountAction('runBackup', { profile: @js($profile['key']) })"
                        >
                            <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 5v14l11-7L8 5Z" />
                            </svg>
                            Run now
                        </button>

                        @if ($latest && $latest['download_url'])
                            <a class="twyxtco-backup-link" href="{{ $latest['download_url'] }}">
                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v11m0 0 4-4m-4 4-4-4M5 19h14" />
                                </svg>
                                Download latest
                            </a>
                        @endif
                    </div>

                    <ul class="twyxtco-backup-list">
                        @foreach ($profile['included'] as $included)
                            <li>{{ $included }}</li>
                        @endforeach
                    </ul>

                    <table class="twyxtco-backup-table">
                        <thead>
                            <tr>
                                <th>Recent backups</th>
                                <th>Disk</th>
                                <th>Size</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentBackups->take(4) as $backup)
                                <tr>
                                    <td>
                                        @if ($backup['download_url'])
                                            <a href="{{ $backup['download_url'] }}" class="font-semibold text-primary-600 dark:text-primary-400" title="{{ $backup['timestamp'] ?? $backup['age'] }}">
                                                {{ $backup['age'] }}
                                            </a>
                                        @else
                                            <span title="{{ $backup['timestamp'] ?? $backup['age'] }}">{{ $backup['age'] }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $backup['disk'] }}</td>
                                    <td>{{ $backup['size_for_humans'] ?? '-' }}</td>
                                    <td>
                                        @if ($backup['path'] && $backup['encoded_path'])
                                            <button
                                                type="button"
                                                class="twyxtco-backup-link twyxtco-backup-link--danger"
                                                wire:click="mountAction('deleteBackup', { profile: @js($backup['profile']), disk: @js($backup['disk']), path: @js($backup['encoded_path']), name: @js($backup['name']) })"
                                            >
                                                Delete
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">No backups found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </article>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>

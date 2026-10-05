import { Site } from '@/types/site';

export type CommandTemplate = {
  id: string;
  label: string;
  name: string;
  command: string;
  frequency?: string;
};

export function cronJobTemplates(site: Site): CommandTemplate[] {
  return [
    {
      id: 'laravel-scheduler',
      label: 'Laravel scheduler',
      name: 'Laravel scheduler',
      command: `cd ${site.path} && php artisan schedule:run >> /dev/null 2>&1`,
      frequency: '* * * * *',
    },
  ];
}

export function workerTemplates(): CommandTemplate[] {
  return [
    {
      id: 'laravel-queue',
      label: 'Laravel queue worker',
      name: 'queue',
      command: 'php artisan queue:work --sleep=3 --tries=3 --max-time=3600',
    },
    {
      id: 'laravel-horizon',
      label: 'Laravel Horizon',
      name: 'horizon',
      command: 'php artisan horizon',
    },
    {
      id: 'laravel-reverb',
      label: 'Laravel Reverb',
      name: 'reverb',
      command: 'php artisan reverb:start',
    },
  ];
}

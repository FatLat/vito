import { Link } from '@inertiajs/react';
import {
  BoxIcon,
  ChartLineIcon,
  ClockIcon,
  CommandIcon,
  GlobeIcon,
  ListEndIcon,
  LogsIcon,
  RocketIcon,
  Settings2Icon,
  SignpostIcon,
  WrenchIcon,
} from 'lucide-react';
import { type NavItem } from '@/types';
import { Server } from '@/types/server';
import { Site } from '@/types/site';
import { cn, currentPath } from '@/lib/utils';

export default function SiteTabs({ server, site }: { server: Server; site: Site }) {
  const base = `/servers/${server.id}/sites/${site.id}`;
  const notReady = server.status !== 'ready';

  const tabs: NavItem[] = [
    { title: 'Application', href: base, onlyActivePath: base, icon: RocketIcon },
    { title: 'Domains', href: `${base}/domains`, icon: GlobeIcon },
    { title: 'Features', href: `${base}/features`, icon: BoxIcon },
    { title: 'Tooling', href: `${base}/tooling`, icon: WrenchIcon, hidden: site.user === server.ssh_user || site.status !== 'ready' },
    { title: 'Commands', href: `${base}/commands`, icon: CommandIcon },
    { title: 'Workers', href: `${base}/workers`, icon: ListEndIcon, isDisabled: notReady, hidden: !server.services['process_manager'] },
    { title: 'CronJobs', href: `${base}/cronjobs`, icon: ClockIcon, isDisabled: notReady },
    { title: 'Redirects', href: `${base}/redirects`, icon: SignpostIcon },
    { title: 'Logs', href: `${base}/logs`, icon: LogsIcon },
    {
      title: 'Stats',
      href: `${base}/stats`,
      icon: ChartLineIcon,
      isDisabled: notReady,
      hidden: !server.services['log_analysis'] || !site.stats_enabled,
    },
    { title: 'Settings', href: `${base}/settings`, icon: Settings2Icon },
  ];

  const path = currentPath();

  return (
    <nav aria-label="Site sections" className="overflow-x-auto border-b px-4">
      <ul className="flex w-max gap-1">
        {tabs
          .filter((tab) => !tab.hidden)
          .map((tab) => {
            const active = tab.onlyActivePath ? path === tab.onlyActivePath : path.startsWith(tab.href);
            return (
              <li key={tab.href}>
                <Link
                  href={tab.href}
                  aria-current={active ? 'page' : undefined}
                  aria-disabled={tab.isDisabled || undefined}
                  className={cn(
                    'text-muted-foreground hover:text-foreground flex items-center gap-2 border-b-2 border-transparent px-3 py-2 text-sm font-medium whitespace-nowrap [&_svg]:size-4',
                    active && 'border-primary text-foreground',
                    tab.isDisabled && 'pointer-events-none opacity-50',
                  )}
                >
                  {tab.icon && <tab.icon />}
                  {tab.title}
                </Link>
              </li>
            );
          })}
      </ul>
    </nav>
  );
}

import { type NavGroup } from '@/types';
import {
  ActivityIcon,
  BoxIcon,
  ChartLineIcon,
  ClockIcon,
  CloudIcon,
  CloudUploadIcon,
  CogIcon,
  DatabaseIcon,
  FlameIcon,
  HistoryIcon,
  HomeIcon,
  KeyIcon,
  ListEndIcon,
  LockIcon,
  LogsIcon,
  MousePointerClickIcon,
  NetworkIcon,
  PackageIcon,
  ServerCogIcon,
  Settings2Icon,
  ShieldIcon,
  UsersIcon,
} from 'lucide-react';
import { ReactNode, useEffect } from 'react';
import { Server } from '@/types/server';
import ServerHeader from '@/pages/servers/components/header';
import Layout from '@/layouts/app/layout';
import { usePage } from '@inertiajs/react';
import { Site } from '@/types/site';
import PHPIcon from '@/icons/php';
import siteHelper from '@/lib/site-helper';
import { useRealtimeRecord } from '@/hooks/use-socket-events';
import SiteTabs from '@/layouts/server/components/site-tabs';

export default function ServerLayout({ children }: { children: ReactNode }) {
  const page = usePage<{
    server: Server;
    site?: Site;
  }>();

  const server = useRealtimeRecord<Server>(page.props.server, 'server')!;
  const isMenuDisabled = server.status !== 'ready';
  const storedSite = siteHelper.getStoredSite();

  useEffect(() => {
    if (storedSite && storedSite.server_id !== page.props.server.id) {
      siteHelper.storeSite(undefined);
    }
  }, [page.props.server.id, storedSite]);

  if (typeof window === 'undefined') {
    return null;
  }

  const base = `/servers/${server.id}`;
  const services = page.props.server.services;

  const navGroups: NavGroup[] = [
    {
      title: '',
      items: [{ title: 'Overview', href: base, onlyActivePath: base, icon: HomeIcon }],
    },
    {
      title: 'Applications',
      items: [
        { title: 'Sites', href: `${base}/sites`, icon: MousePointerClickIcon, isDisabled: isMenuDisabled, hidden: !services['webserver'] },
        {
          title: 'Database',
          href: `${base}/database`,
          icon: DatabaseIcon,
          isDisabled: isMenuDisabled,
          hidden: !services['database'],
          children: [
            { title: 'Databases', href: `${base}/database`, onlyActivePath: `${base}/database`, icon: DatabaseIcon },
            { title: 'Users', href: `${base}/database/users`, icon: UsersIcon },
          ],
        },
        { title: 'Backups', href: `${base}/backups`, icon: CloudUploadIcon, isDisabled: isMenuDisabled },
        { title: 'PHP', href: `${base}/php`, icon: PHPIcon, isDisabled: isMenuDisabled, hidden: !services['php'] },
        { title: 'Workers', href: `${base}/workers`, icon: ListEndIcon, isDisabled: isMenuDisabled, hidden: !services['process_manager'] },
        { title: 'CronJobs', href: `${base}/cronjobs`, icon: ClockIcon, isDisabled: isMenuDisabled },
      ],
    },
    {
      title: 'Security',
      items: [
        { title: 'Security', href: `${base}/security`, onlyActivePath: `${base}/security`, icon: ShieldIcon, isDisabled: isMenuDisabled },
        { title: 'Firewall', href: `${base}/firewall`, icon: FlameIcon, isDisabled: isMenuDisabled, hidden: !services['firewall'] },
        { title: 'SSH Keys', href: `${base}/ssh-keys`, icon: KeyIcon, isDisabled: isMenuDisabled },
        { title: 'SSL', href: `${base}/ssl`, icon: LockIcon, isDisabled: isMenuDisabled },
        { title: 'Command history', href: `${base}/system/commands`, icon: HistoryIcon, isDisabled: isMenuDisabled },
      ],
    },
    {
      title: 'System',
      items: [
        { title: 'System', href: `${base}/system`, onlyActivePath: `${base}/system`, icon: ServerCogIcon, isDisabled: isMenuDisabled },
        { title: 'Processes', href: `${base}/system/processes`, icon: ActivityIcon, isDisabled: isMenuDisabled },
        { title: 'Updates', href: `${base}/system/updates`, icon: PackageIcon, isDisabled: isMenuDisabled },
        { title: 'Monitoring', href: `${base}/monitoring`, icon: ChartLineIcon, isDisabled: isMenuDisabled },
        { title: 'Services', href: `${base}/services`, icon: CogIcon, isDisabled: isMenuDisabled },
        { title: 'Network', href: `${base}/network`, icon: NetworkIcon, isDisabled: isMenuDisabled },
        {
          title: 'Logs',
          href: `${base}/logs`,
          icon: LogsIcon,
          children: [
            { title: 'Server logs', href: `${base}/logs`, onlyActivePath: `${base}/logs`, icon: LogsIcon },
            { title: 'Service logs', href: `${base}/logs/services`, onlyActivePath: `${base}/logs/services`, icon: CogIcon },
            { title: 'Custom logs', href: `${base}/logs/remote`, onlyActivePath: `${base}/logs/remote`, icon: CloudIcon },
          ],
        },
        { title: 'Features', href: `${base}/features`, icon: BoxIcon, isDisabled: isMenuDisabled },
        { title: 'Settings', href: `${base}/settings`, icon: Settings2Icon },
      ],
    },
  ];

  return (
    <Layout secondNavGroups={navGroups} secondNavTitle={page.props.server.name}>
      <ServerHeader server={server} site={page.props.site} />
      {page.props.site && <SiteTabs server={server} site={page.props.site} />}

      <div>{children}</div>
    </Layout>
  );
}

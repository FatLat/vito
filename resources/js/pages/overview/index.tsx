import { Head, Link, usePage } from '@inertiajs/react';
import { ReactNode } from 'react';
import { CloudUploadIcon, GlobeIcon, MousePointerClickIcon, ServerIcon } from 'lucide-react';
import { Overview } from '@/types/overview';
import Layout from '@/layouts/app/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import DateTime from '@/components/date-time';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';

function ListCard({ title, empty, children }: { title: string; empty?: string; children: ReactNode[] }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent className="p-0">
        <Table>
          <TableBody>
            {children.length === 0 ? (
              <TableRow>
                <TableCell className="text-muted-foreground text-center">{empty}</TableCell>
              </TableRow>
            ) : (
              children
            )}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
}

export default function OverviewPage() {
  const { overview } = usePage<{ overview: Overview }>().props;

  const tiles = [
    { title: 'Servers', value: overview.counts.servers, href: '/servers', icon: ServerIcon },
    { title: 'Sites', value: overview.counts.sites, href: '/sites', icon: MousePointerClickIcon },
    { title: 'Backups', value: overview.counts.backups, href: '/backups', icon: CloudUploadIcon },
    { title: 'Domains', value: overview.counts.domains, href: '/domains', icon: GlobeIcon },
  ];

  return (
    <Layout>
      <Head title="Overview" />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Overview" description="Servers, sites and anything that needs attention in this project" />
        </HeaderContainer>

        <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
          {tiles.map((tile) => (
            <Link key={tile.title} href={tile.href} className="rounded-xl">
              <Card className="hover:bg-muted/50 h-full transition-colors">
                <CardContent className="flex items-center justify-between gap-2 p-4">
                  <div className="flex flex-col gap-1">
                    <span className="text-muted-foreground text-sm">{tile.title}</span>
                    <span className="text-2xl font-semibold">{tile.value}</span>
                  </div>
                  <tile.icon className="text-muted-foreground size-6" aria-hidden />
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>

        {(overview.backup_problems.length > 0 || overview.expiring_ssls.length > 0) && (
          <div className="grid gap-4 lg:grid-cols-2">
            {overview.backup_problems.length > 0 && (
              <ListCard title="Backups that need attention">
                {overview.backup_problems.map((backup) => (
                  <TableRow key={backup.id}>
                    <TableCell>
                      <Link href={`/servers/${backup.server_id}/backups`} className="hover:underline">
                        {backup.target}
                      </Link>
                      <div className="text-muted-foreground text-xs">{backup.server_name}</div>
                    </TableCell>
                    <TableCell className="text-right">
                      <Badge variant={backup.problem === 'failed' ? 'danger' : 'warning'}>{backup.problem}</Badge>
                    </TableCell>
                  </TableRow>
                ))}
              </ListCard>
            )}
            {overview.expiring_ssls.length > 0 && (
              <ListCard title="SSL certificates expiring soon">
                {overview.expiring_ssls.map((ssl) => (
                  <TableRow key={ssl.id}>
                    <TableCell>
                      {ssl.server_id && ssl.site_id ? (
                        <Link href={`/servers/${ssl.server_id}/sites/${ssl.site_id}/domains`} className="hover:underline">
                          {ssl.domain}
                        </Link>
                      ) : (
                        ssl.domain
                      )}
                    </TableCell>
                    <TableCell className="text-right">
                      <DateTime date={ssl.expires_at} format="YYYY-MM-DD" />
                    </TableCell>
                  </TableRow>
                ))}
              </ListCard>
            )}
          </div>
        )}

        <div className="grid gap-4 lg:grid-cols-2">
          <ListCard title="Recent servers" empty="No servers yet">
            {overview.servers.map((server) => (
              <TableRow key={server.id}>
                <TableCell>
                  <Link href={`/servers/${server.id}`} className="hover:underline">
                    {server.name}
                  </Link>
                  <div className="text-muted-foreground text-xs">{server.ip}</div>
                </TableCell>
                <TableCell className="text-right">
                  <Badge variant={server.status_color}>{server.status}</Badge>
                </TableCell>
              </TableRow>
            ))}
          </ListCard>
          <ListCard title="Recent sites" empty="No sites yet">
            {overview.sites.map((site) => (
              <TableRow key={site.id}>
                <TableCell>
                  <Link href={`/servers/${site.server_id}/sites/${site.id}`} className="hover:underline">
                    {site.domain}
                  </Link>
                  <div className="text-muted-foreground text-xs">{site.server_name}</div>
                </TableCell>
                <TableCell className="text-right">
                  <Badge variant={site.status_color}>{site.status}</Badge>
                </TableCell>
              </TableRow>
            ))}
          </ListCard>
        </div>
      </Container>
    </Layout>
  );
}

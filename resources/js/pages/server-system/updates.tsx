import { Head, useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon, TriangleAlertIcon } from 'lucide-react';
import { Server } from '@/types/server';
import { ServerLog } from '@/types/server-log';
import { UpgradablePackages } from '@/types/server-system';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import DateTime from '@/components/date-time';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useDialog } from '@/hooks/use-dialog';
import { useRealtimeRecord } from '@/hooks/use-socket-events';
import QueryState from '@/pages/server-system/components/query-state';
import { useSystemData } from '@/pages/server-system/components/use-system-data';

export default function Updates() {
  const page = usePage<{ server: Server; logs: ServerLog[] }>();
  const server = useRealtimeRecord<Server>(page.props.server, 'server')!;
  const dialog = useDialog();
  const checkForm = useForm();
  const busy = server.status !== 'ready';
  const query = useSystemData<UpgradablePackages>(server, 'updates/json', {
    key: [server.updates, server.kernel_updates],
    enabled: !busy,
  });

  const confirm = (title: string, description: string, confirmLabel: string, url: string) =>
    dialog.confirm.open({ title, description, variant: 'destructive', confirmLabel, method: 'post', url });

  return (
    <ServerLayout>
      <Head title={`Updates - ${server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Updates" description="Pending package and kernel updates, restarts and their logs" />
          <div className="flex flex-wrap items-center gap-2">
            <Button variant="outline" disabled={busy || checkForm.processing} onClick={() => checkForm.post(`/servers/${server.id}/check-for-updates`, { preserveScroll: true, onSuccess: () => query.refetch() })}>
              {checkForm.processing && <LoaderCircleIcon className="animate-spin" />}
              Check for updates
            </Button>
            <Button
              variant="outline"
              disabled={busy}
              onClick={() =>
                confirm(
                  `Restart ${server.name}?`,
                  'Are you sure you want to restart this server? Sites and services hosted on this server will be unavailable while it restarts. Connections in flight will be dropped.',
                  'Restart',
                  `/servers/${server.id}/reboot`,
                )
              }
            >
              Restart
            </Button>
          </div>
        </HeaderContainer>

        {busy && (
          <Alert>
            <LoaderCircleIcon className="animate-spin" />
            <AlertDescription>Server status is {server.status}. Open the latest log below to follow it live.</AlertDescription>
          </Alert>
        )}

        {query.data?.reboot_required && (
          <Alert variant="destructive">
            <TriangleAlertIcon />
            <AlertDescription>A restart is required to finish applying installed updates.</AlertDescription>
          </Alert>
        )}

        <div className="grid gap-4 lg:grid-cols-2">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
              <CardTitle>Packages ({server.updates})</CardTitle>
              <Button
                disabled={busy || server.updates === 0}
                onClick={() =>
                  confirm(
                    `Update ${server.name}?`,
                    'Apply the pending OS package updates to this server? The upgrade can take several minutes and may briefly restart affected services. A server restart may be required afterwards.',
                    'Update',
                    `/servers/${server.id}/update`,
                  )
                }
              >
                Update packages
              </Button>
            </CardHeader>
          </Card>
          <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-2">
              <CardTitle>Kernel ({server.kernel_updates})</CardTitle>
              <Button
                disabled={busy || server.kernel_updates === 0}
                onClick={() =>
                  confirm(
                    `Update kernel on ${server.name}?`,
                    'This installs the pending kernel packages (a full upgrade that may install or remove packages), then restarts the server to boot the new kernel. The server will be unavailable for a minute or two and connections in flight will be dropped.',
                    'Update & restart',
                    `/servers/${server.id}/update-kernel`,
                  )
                }
              >
                Update kernel
              </Button>
            </CardHeader>
          </Card>
        </div>

        <QueryState query={query} cells={3} />

        {query.data && query.data.packages.length > 0 && (
          <div className="rounded-md border shadow-xs">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Package</TableHead>
                  <TableHead>Installed</TableHead>
                  <TableHead>Available</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {query.data.packages.map((pkg) => (
                  <TableRow key={pkg.name}>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        {pkg.name}
                        {pkg.kernel && <Badge variant="warning">kernel</Badge>}
                      </div>
                    </TableCell>
                    <TableCell className="font-mono text-xs">{pkg.current}</TableCell>
                    <TableCell className="font-mono text-xs">{pkg.candidate}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}

        <Card>
          <CardHeader>
            <CardTitle>Recent logs</CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            <Table>
              <TableBody>
                {page.props.logs.length === 0 && (
                  <TableRow>
                    <TableCell className="text-muted-foreground text-center">No update logs yet</TableCell>
                  </TableRow>
                )}
                {page.props.logs.map((log) => (
                  <TableRow key={log.id}>
                    <TableCell>{log.type}</TableCell>
                    <TableCell>
                      <DateTime date={log.created_at} />
                    </TableCell>
                    <TableCell className="text-right">
                      <Button variant="outline" size="sm" onClick={() => dialog.logViewer.open({ serverId: server.id, logId: log.id, title: log.type })}>
                        View
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </Container>
    </ServerLayout>
  );
}

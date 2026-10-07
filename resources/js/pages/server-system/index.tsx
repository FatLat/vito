import { Head, usePage } from '@inertiajs/react';
import { RefreshCwIcon } from 'lucide-react';
import { ReactNode } from 'react';
import { Server } from '@/types/server';
import { SystemOverview, SystemPathSize } from '@/types/server-system';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import { useDialog } from '@/hooks/use-dialog';
import { formatBytes } from '@/lib/utils';
import QueryState from '@/pages/server-system/components/query-state';
import { useSystemData } from '@/pages/server-system/components/use-system-data';

const INFO_LABELS: Record<string, string> = {
  hostname: 'Hostname',
  os: 'Operating system',
  kernel: 'Kernel',
  architecture: 'Architecture',
  cpu: 'CPU',
  timezone: 'Timezone',
};

function SizeTable({ rows, action }: { rows: SystemPathSize[]; action?: (row: SystemPathSize) => ReactNode }) {
  return (
    <Table>
      <TableBody>
        {rows.map((row) => (
          <TableRow key={row.path}>
            <TableCell className="font-mono text-xs break-all whitespace-normal">{row.path}</TableCell>
            <TableCell className="text-right whitespace-nowrap">{formatBytes(row.size)}</TableCell>
            {action && <TableCell className="w-0 text-right">{action(row)}</TableCell>}
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}

export default function SystemIndex() {
  const page = usePage<{ server: Server }>();
  const dialog = useDialog();
  const query = useSystemData<SystemOverview>(page.props.server, 'json');
  const data = query.data;

  const clearLog = (path: string) => {
    dialog.confirm.open({
      title: 'Clear log file',
      description: `The contents of ${path} will be removed. This cannot be undone.`,
      variant: 'destructive',
      confirmLabel: 'Clear',
      method: 'post',
      url: `/servers/${page.props.server.id}/system/logs/clear`,
      data: { path },
      onSuccess: () => query.refetch(),
    });
  };

  return (
    <ServerLayout>
      <Head title={`System - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="System" description="Server details, disk usage and the largest log files" />
          <Button variant="outline" onClick={() => query.refetch()} disabled={query.isFetching} aria-label="Refresh">
            <RefreshCwIcon className={query.isFetching ? 'animate-spin' : ''} />
            <span className="hidden lg:block">Refresh</span>
          </Button>
        </HeaderContainer>

        <QueryState query={query} cells={2} />

        {data && (
          <div className="grid gap-4 lg:grid-cols-2">
            <Card>
              <CardHeader>
                <CardTitle>Information</CardTitle>
              </CardHeader>
              <CardContent className="p-0">
                <Table>
                  <TableBody>
                    {Object.entries(INFO_LABELS).map(([key, label]) => (
                      <TableRow key={key}>
                        <TableCell className="text-muted-foreground">{label}</TableCell>
                        <TableCell className="break-all whitespace-normal">{data.info[key] || '-'}</TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Disks</CardTitle>
              </CardHeader>
              <CardContent className="flex flex-col gap-4">
                {data.disks.map((disk) => {
                  const percent = disk.size ? Math.min(100, Math.round((disk.used / disk.size) * 100)) : 0;
                  return (
                    <div key={disk.mount} className="flex flex-col gap-2">
                      <div className="flex items-center justify-between gap-2 text-sm">
                        <span className="font-mono">{disk.mount}</span>
                        <span className="text-muted-foreground">
                          {formatBytes(disk.used)} / {formatBytes(disk.size)} ({percent}%)
                        </span>
                      </div>
                      <Progress value={percent} aria-label={`${disk.mount} usage`} />
                    </div>
                  );
                })}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Largest directories</CardTitle>
              </CardHeader>
              <CardContent className="p-0">
                <SizeTable rows={data.directories} />
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Largest log files</CardTitle>
              </CardHeader>
              <CardContent className="p-0">
                <SizeTable
                  rows={data.logs}
                  action={(row) => (
                    <Button variant="outline" size="sm" onClick={() => clearLog(row.path)} disabled={row.size === 0}>
                      Clear
                    </Button>
                  )}
                />
              </CardContent>
            </Card>
          </div>
        )}
      </Container>
    </ServerLayout>
  );
}

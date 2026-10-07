import { Head, usePage } from '@inertiajs/react';
import { MoreVerticalIcon, RefreshCwIcon } from 'lucide-react';
import { useState } from 'react';
import { Server } from '@/types/server';
import { SystemProcess } from '@/types/server-system';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useDialog } from '@/hooks/use-dialog';
import { formatBytes, humanizeSeconds } from '@/lib/utils';
import QueryState from '@/pages/server-system/components/query-state';
import { useSystemData } from '@/pages/server-system/components/use-system-data';

export default function Processes() {
  const page = usePage<{ server: Server }>();
  const dialog = useDialog();
  const [search, setSearch] = useState('');
  const query = useSystemData<SystemProcess[]>(page.props.server, 'processes/json', { refetchInterval: 10_000 });
  const term = search.trim().toLowerCase();
  const processes = (query.data ?? []).filter(
    (proc) => !term || proc.command.toLowerCase().includes(term) || proc.user.toLowerCase().includes(term) || String(proc.pid) === term,
  );

  const kill = (proc: SystemProcess, signal: 'TERM' | 'KILL') => {
    dialog.confirm.open({
      title: signal === 'TERM' ? 'Stop process' : 'Force kill process',
      description:
        signal === 'TERM'
          ? `Process ${proc.pid} will be asked to stop (SIGTERM).`
          : `Process ${proc.pid} will be killed immediately (SIGKILL). Unsaved work in it is lost.`,
      variant: 'destructive',
      confirmLabel: signal === 'TERM' ? 'Stop' : 'Kill',
      method: 'post',
      url: `/servers/${page.props.server.id}/system/processes/kill`,
      data: { pid: proc.pid, signal },
      onSuccess: () => query.refetch(),
    });
  };

  return (
    <ServerLayout>
      <Head title={`Processes - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Processes" description="Top 50 processes by CPU usage, refreshed every 10 seconds" />
          <div className="flex items-center gap-2">
            <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search" aria-label="Search processes" className="w-40" />
            <Button variant="outline" onClick={() => query.refetch()} disabled={query.isFetching} aria-label="Refresh">
              <RefreshCwIcon className={query.isFetching ? 'animate-spin' : ''} />
            </Button>
          </div>
        </HeaderContainer>

        <QueryState query={query} cells={6} />

        {query.data && (
          <div className="rounded-md border shadow-xs">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>PID</TableHead>
                  <TableHead>User</TableHead>
                  <TableHead className="text-right">CPU</TableHead>
                  <TableHead className="text-right">Memory</TableHead>
                  <TableHead>Running for</TableHead>
                  <TableHead>Command</TableHead>
                  <TableHead />
                </TableRow>
              </TableHeader>
              <TableBody>
                {processes.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={7} className="text-muted-foreground text-center">
                      No processes found
                    </TableCell>
                  </TableRow>
                )}
                {processes.map((proc) => (
                  <TableRow key={proc.pid}>
                    <TableCell>{proc.pid}</TableCell>
                    <TableCell>{proc.user}</TableCell>
                    <TableCell className="text-right">{proc.cpu.toFixed(1)}%</TableCell>
                    <TableCell className="text-right whitespace-nowrap">
                      {formatBytes(proc.rss)} ({proc.memory.toFixed(1)}%)
                    </TableCell>
                    <TableCell className="whitespace-nowrap">{humanizeSeconds(proc.elapsed)}</TableCell>
                    <TableCell className="max-w-md truncate font-mono text-xs" title={proc.command}>
                      {proc.command}
                    </TableCell>
                    <TableCell className="text-right">
                      <DropdownMenu modal={false}>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" className="h-8 w-8 p-0" aria-label={`Actions for process ${proc.pid}`}>
                            <MoreVerticalIcon />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem onSelect={() => kill(proc, 'TERM')}>Stop (SIGTERM)</DropdownMenuItem>
                          <DropdownMenuItem variant="destructive" onSelect={() => kill(proc, 'KILL')}>
                            Kill (SIGKILL)
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </Container>
    </ServerLayout>
  );
}

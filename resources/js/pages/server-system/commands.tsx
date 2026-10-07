import { Head, usePage } from '@inertiajs/react';
import { RefreshCwIcon } from 'lucide-react';
import { useState } from 'react';
import { Server } from '@/types/server';
import { CommandHistoryEntry } from '@/types/server-system';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import QueryState from '@/pages/server-system/components/query-state';
import { useSystemData } from '@/pages/server-system/components/use-system-data';

export default function Commands() {
  const page = usePage<{ server: Server }>();
  const [source, setSource] = useState<CommandHistoryEntry['source']>('sudo');
  const [search, setSearch] = useState('');
  const query = useSystemData<CommandHistoryEntry[]>(page.props.server, 'commands/json');
  const columns = source === 'sudo' ? 3 : 2;
  const term = search.trim().toLowerCase();
  const entries = (query.data ?? []).filter(
    (entry) => entry.source === source && (!term || entry.command.toLowerCase().includes(term) || entry.user.toLowerCase().includes(term)),
  );

  return (
    <ServerLayout>
      <Head title={`Command history - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Command history" description="Commands run with sudo and the shell history of each user" />
          <div className="flex items-center gap-2">
            <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Search" aria-label="Search commands" className="w-40" />
            <Button variant="outline" onClick={() => query.refetch()} disabled={query.isFetching} aria-label="Refresh">
              <RefreshCwIcon className={query.isFetching ? 'animate-spin' : ''} />
            </Button>
          </div>
        </HeaderContainer>

        <Tabs value={source} onValueChange={(value) => setSource(value as CommandHistoryEntry['source'])}>
          <TabsList>
            <TabsTrigger value="sudo">sudo</TabsTrigger>
            <TabsTrigger value="bash">Shell history</TabsTrigger>
          </TabsList>
        </Tabs>

        <QueryState query={query} cells={columns} />

        {query.data && (
          <div className="rounded-md border shadow-xs">
            <Table>
              <TableHeader>
                <TableRow>
                  {source === 'sudo' && <TableHead>Time</TableHead>}
                  <TableHead>User</TableHead>
                  <TableHead>Command</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {entries.length === 0 && (
                  <TableRow>
                    <TableCell colSpan={columns} className="text-muted-foreground text-center">
                      No commands found
                    </TableCell>
                  </TableRow>
                )}
                {entries.map((entry, index) => (
                  <TableRow key={`${entry.time}-${entry.user}-${index}`}>
                    {source === 'sudo' && <TableCell className="whitespace-nowrap">{entry.time ?? '-'}</TableCell>}
                    <TableCell className="whitespace-nowrap">
                      {entry.user}
                      {entry.run_as && entry.run_as !== entry.user && <span className="text-muted-foreground"> → {entry.run_as}</span>}
                    </TableCell>
                    <TableCell className="font-mono text-xs break-all whitespace-normal">{entry.command}</TableCell>
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

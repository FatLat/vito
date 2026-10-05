import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { CheckIcon, MinusIcon } from 'lucide-react';

const PERMISSIONS: Array<{ label: string; user: boolean; admin: boolean; owner: boolean }> = [
  { label: 'View servers, sites and logs', user: true, admin: true, owner: true },
  { label: 'Create and manage servers, sites, databases and deployments', user: false, admin: true, owner: true },
  { label: 'Edit project settings, invite and remove members', user: false, admin: true, owner: true },
  { label: 'Delete servers', user: false, admin: false, owner: true },
  { label: 'Delete the project', user: false, admin: false, owner: true },
];

function Allowed({ allowed }: { allowed: boolean }) {
  return allowed ? (
    <CheckIcon className="text-success mx-auto size-4" aria-label="Allowed" />
  ) : (
    <MinusIcon className="text-muted-foreground mx-auto size-4" aria-label="Not allowed" />
  );
}

export default function RolePermissions() {
  return (
    <Table>
      <TableHeader>
        <TableRow>
          <TableHead>Permission</TableHead>
          <TableHead className="text-center">User</TableHead>
          <TableHead className="text-center">Admin</TableHead>
          <TableHead className="text-center">Owner</TableHead>
        </TableRow>
      </TableHeader>
      <TableBody>
        {PERMISSIONS.map((permission) => (
          <TableRow key={permission.label}>
            <TableCell className="whitespace-normal">{permission.label}</TableCell>
            <TableCell>
              <Allowed allowed={permission.user} />
            </TableCell>
            <TableCell>
              <Allowed allowed={permission.admin} />
            </TableCell>
            <TableCell>
              <Allowed allowed={permission.owner} />
            </TableCell>
          </TableRow>
        ))}
      </TableBody>
    </Table>
  );
}

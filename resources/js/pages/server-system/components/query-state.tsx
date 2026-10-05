import { UseQueryResult } from '@tanstack/react-query';
import axios from 'axios';
import { TriangleAlertIcon } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { TableSkeleton } from '@/components/table-skeleton';

function errorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    return error.response?.data?.message || error.message;
  }
  return 'Could not load data from the server.';
}

export default function QueryState({ query, cells }: { query: UseQueryResult; cells: number }) {
  if (query.isLoading) {
    return <TableSkeleton cells={cells} rows={5} />;
  }

  if (query.isError) {
    return (
      <Alert variant="destructive">
        <TriangleAlertIcon />
        <AlertDescription>{errorMessage(query.error)}</AlertDescription>
      </Alert>
    );
  }

  return null;
}

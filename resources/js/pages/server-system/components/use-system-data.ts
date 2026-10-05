import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { Server } from '@/types/server';

type Options = {
  refetchInterval?: number;
  key?: unknown[];
  enabled?: boolean;
};

export function useSystemData<T>(server: Server, path: string, { refetchInterval, key = [], enabled = true }: Options = {}) {
  return useQuery<T>({
    queryKey: ['server-system', server.id, path, ...key],
    queryFn: async () => (await axios.get<T>(`/servers/${server.id}/system/${path}`)).data,
    refetchInterval,
    enabled,
    retry: false,
  });
}

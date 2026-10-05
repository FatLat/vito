import { useState } from 'react';
import { router } from '@inertiajs/react';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { LoaderCircleIcon } from 'lucide-react';
import DateTime from '@/components/date-time';
import { Button } from '@/components/ui/button';
import { Site } from '@/types/site';
import { EnvVersion } from '@/types/env';

export default function EnvHistory({
  site,
  path,
  hasUnsavedChanges,
  onRestored,
}: {
  site: Site;
  path: string;
  hasUnsavedChanges: boolean;
  onRestored: () => void;
}) {
  const [confirmingId, setConfirmingId] = useState<number | null>(null);
  const [restoringId, setRestoringId] = useState<number | null>(null);

  const query = useQuery({
    queryKey: ['application.env-versions', site.server_id, site.id],
    queryFn: async () => {
      const response = await axios.get<EnvVersion[]>(`/servers/${site.server_id}/sites/${site.id}/env/versions`);
      return response.data;
    },
    refetchOnWindowFocus: false,
  });

  const versions = (query.data ?? []).filter((version) => version.path === path);

  const restore = (version: EnvVersion) => {
    setRestoringId(version.id);
    router.post(
      `/servers/${site.server_id}/sites/${site.id}/env/versions/${version.id}/restore`,
      {},
      {
        preserveScroll: true,
        onSuccess: () => {
          setConfirmingId(null);
          query.refetch();
          onRestored();
        },
        onFinish: () => setRestoringId(null),
      },
    );
  };

  if (query.isLoading) {
    return <p className="text-muted-foreground text-sm">Loading history...</p>;
  }

  if (query.isError) {
    return <p className="text-destructive text-sm">Failed to load the history of this file.</p>;
  }

  if (versions.length === 0) {
    return <p className="text-muted-foreground text-sm">No earlier versions of this file yet. A copy is kept each time it is saved from Vito.</p>;
  }

  return (
    <div className="flex flex-col divide-y rounded-md border">
      {versions.map((version) => (
        <div key={version.id} className="flex items-center justify-between gap-4 px-3 py-2 text-sm">
          <div className="flex flex-col">
            <DateTime date={version.created_at} />
            <span className="text-muted-foreground text-xs">Replaced by {version.user ?? 'an automated change'}</span>
          </div>
          {confirmingId === version.id ? (
            <div className="flex items-center gap-2">
              {hasUnsavedChanges && <span className="text-muted-foreground text-xs">Unsaved changes will be lost.</span>}
              <Button type="button" size="sm" variant="outline" onClick={() => setConfirmingId(null)} disabled={restoringId !== null}>
                Cancel
              </Button>
              <Button type="button" size="sm" onClick={() => restore(version)} disabled={restoringId !== null}>
                {restoringId === version.id && <LoaderCircleIcon className="animate-spin" />}
                Confirm restore
              </Button>
            </div>
          ) : (
            <Button type="button" size="sm" variant="outline" onClick={() => setConfirmingId(version.id)} disabled={restoringId !== null}>
              Restore
            </Button>
          )}
        </div>
      ))}
    </div>
  );
}

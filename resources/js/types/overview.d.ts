export interface Overview {
  counts: {
    servers: number;
    sites: number;
    backups: number;
    domains: number;
  };
  servers: {
    id: number;
    name: string;
    ip: string | null;
    status: string;
    status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  }[];
  sites: {
    id: number;
    server_id: number;
    server_name: string;
    domain: string;
    status: string;
    status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  }[];
  backup_problems: {
    id: number;
    server_id: number;
    server_name: string;
    target: string;
    problem: 'overdue' | 'failed';
  }[];
  expiring_ssls: {
    id: number;
    server_id: number | null;
    site_id: number | null;
    domain: string;
    expires_at: string;
  }[];
}

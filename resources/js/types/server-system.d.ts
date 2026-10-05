export interface SystemOverview {
  info: Record<string, string>;
  disks: {
    mount: string;
    filesystem: string;
    size: number;
    used: number;
    available: number;
  }[];
  directories: SystemPathSize[];
  logs: SystemPathSize[];
}

export interface SystemPathSize {
  path: string;
  size: number;
}

export interface SystemProcess {
  pid: number;
  user: string;
  cpu: number;
  memory: number;
  rss: number;
  elapsed: number;
  command: string;
}

export interface UpgradablePackages {
  reboot_required: boolean;
  packages: {
    name: string;
    current: string;
    candidate: string;
    kernel: boolean;
  }[];
}

export interface CommandHistoryEntry {
  source: 'sudo' | 'bash';
  time: string | null;
  user: string;
  run_as: string | null;
  command: string;
}

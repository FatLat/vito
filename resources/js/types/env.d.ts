export interface EnvVariable {
  id: string;
  key: string;
  value: string;
  isSecret: boolean;
  isNew?: boolean; // True for variables being created in this session
}

export interface EnvVersion {
  id: number;
  site_id: number;
  path: string;
  user: string | null;
  created_at: string;
}

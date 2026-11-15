export interface ImportReport {
  taskId: string;
  status: string;
  imported: number;
  errors: string[];
}
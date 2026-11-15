// frontend/src/app/services/import.service.ts
import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface ImportReport {
  taskId: string;
  status: string;
  imported: number;
  errors: string[];
  createdAt?: string; // Если добавили в бэкенд
}

@Injectable({ providedIn: 'root' })
export class ImportService {
  private http = inject(HttpClient);
  private apiUrl = 'http://localhost:8080/api/import';

  uploadFile(file: File): Observable<{ taskId: string }> {
    const formData = new FormData();
    formData.append('file', file);
    return this.http.post<{ taskId: string }>(this.apiUrl, formData);
  }

  getImportStatus(taskId: string): Observable<ImportReport> {
    return this.http.get<ImportReport>(`${this.apiUrl}/status/${taskId}`);
  }

  // 👇 Новый метод для получения списка
  getImportList(): Observable<ImportReport[]> {
    return this.http.get<ImportReport[]>(`${this.apiUrl}/list`);
  }
}
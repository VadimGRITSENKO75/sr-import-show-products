import { Component, inject, ChangeDetectorRef } from '@angular/core';
import { NgIf } from '@angular/common';
import { MatButtonModule } from '@angular/material/button';
import { MatProgressBarModule } from '@angular/material/progress-bar';
import { Router } from '@angular/router'; // ← Импорт роутера
import { ImportService } from '../../services/import.service';

@Component({
  selector: 'app-import',
  standalone: true,
  imports: [NgIf, MatButtonModule, MatProgressBarModule],
  template: `
    <div style="margin: 20px auto; max-width: 600px;">
      <h2>Импорт товаров</h2>
      <input type="file" accept=".xlsx" (change)="onFileSelected($event)" />
      <button mat-raised-button color="primary" (click)="upload()" [disabled]="!selectedFile || uploading" style="margin-left: 10px;">
        Загрузить
      </button>
      <mat-progress-bar *ngIf="uploading" mode="indeterminate" style="margin-top: 20px;"></mat-progress-bar>
      <p *ngIf="uploading" style="text-align: center;">Идёт обработка файла, подождите...</p>
    </div>
  `,
})
export class ImportComponent {
  private importService = inject(ImportService);
  private cdr = inject(ChangeDetectorRef);
  private router = inject(Router); // ← Внедряем роутер

  selectedFile: File | null = null;
  uploading = false;

  onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files?.length) {
      this.selectedFile = input.files[0];
    }
  }

  upload() {
    if (!this.selectedFile) return;
    this.uploading = true;

    this.importService.uploadFile(this.selectedFile).subscribe({
      next: (res: any) => {
        this.pollStatus(res.taskId);
      },
      error: (err) => {
        this.uploading = false;
        alert('Ошибка загрузки: ' + err.message);
        this.cdr.detectChanges();
      },
    });
  }

  private pollStatus(taskId: string) {
    const interval = setInterval(() => {
      this.importService.getImportStatus(taskId).subscribe({
        next: (res: any) => {
          if (['done', 'done_with_errors', 'failed'].includes(res.status)) {
            clearInterval(interval);
            this.uploading = false;
            this.cdr.detectChanges();
            
            // 👇 ПЕРЕХОДИМ НА СТРАНИЦУ ОТЧЕТА
            this.router.navigate(['/import/report', taskId]); 
          }
        },
        error: () => {
          clearInterval(interval);
          this.uploading = false;
          alert('Ошибка при получении статуса');
          this.cdr.detectChanges();
        },
      });
    }, 2000);
  }
}
import { Component, inject, OnInit, ChangeDetectorRef } from '@angular/core';
import { NgIf, AsyncPipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { MatTableModule } from '@angular/material/table';
import { MatButtonModule } from '@angular/material/button';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ImportService, ImportReport } from '../../services/import.service';

@Component({
  selector: 'app-import-list',
  standalone: true,
  imports: [NgIf, AsyncPipe, RouterLink, MatTableModule, MatButtonModule, MatProgressSpinnerModule],
  template: `
    <div style="padding: 20px; max-width: 1000px; margin: 0 auto;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>История импортов</h2>
        <button mat-raised-button color="primary" routerLink="/import">Новый импорт</button>
      </div>

      <!-- Спиннер -->
      <div *ngIf="loading" style="text-align: center; margin-top: 50px;">
        <mat-spinner diameter="40" style="margin: 0 auto;"></mat-spinner>
      </div>

      <!-- Таблица (если есть данные) -->
      <table mat-table [dataSource]="reports" *ngIf="!loading && reports.length > 0" style="width: 100%;">
        <ng-container matColumnDef="taskId">
          <th mat-header-cell *matHeaderCellDef>ID Задачи</th>
          <td mat-cell *matCellDef="let r">{{ r.taskId.substring(0, 8) }}...</td>
        </ng-container>

        <ng-container matColumnDef="status">
          <th mat-header-cell *matHeaderCellDef>Статус</th>
          <td mat-cell *matCellDef="let r">
            <span [style.color]="r.errors?.length > 0 ? 'orange' : 'green'">
              {{ r.status === 'done' ? 'Успешно' : 'С ошибками' }}
            </span>
          </td>
        </ng-container>

        <ng-container matColumnDef="imported">
          <th mat-header-cell *matHeaderCellDef>Импортировано</th>
          <td mat-cell *matCellDef="let r">{{ r.imported }}</td>
        </ng-container>

        <ng-container matColumnDef="errors">
          <th mat-header-cell *matHeaderCellDef>Ошибки</th>
          <td mat-cell *matCellDef="let r">{{ r.errors?.length || 0 }}</td>
        </ng-container>

        <ng-container matColumnDef="actions">
          <th mat-header-cell *matHeaderCellDef></th>
          <td mat-cell *matCellDef="let r">
            <button mat-button color="primary" [routerLink]="['/import/report', r.taskId]">Детали</button>
          </td>
        </ng-container>

        <tr mat-header-row *matHeaderRowDef="displayedColumns"></tr>
        <tr mat-row *matRowDef="let row; columns: displayedColumns;"></tr>
      </table>
      
      <!-- Если пусто -->
      <div *ngIf="!loading && reports.length === 0" style="text-align: center; margin-top: 50px; color: gray;">
        <p>История импортов пуста.</p>
      </div>
    </div>
  `
})
export class ImportListComponent implements OnInit {
  private importService = inject(ImportService);
  private cdr = inject(ChangeDetectorRef); // Для zoneless режима

  reports: ImportReport[] = [];
  loading = true;
  displayedColumns = ['taskId', 'status', 'imported', 'errors', 'actions'];

  ngOnInit() {
    this.importService.getImportList().subscribe({
      next: (data) => {
        this.reports = data;
        this.loading = false;
        this.cdr.detectChanges(); // Принудительно обновляем UI
      },
      error: (err) => {
        console.error('Ошибка загрузки истории:', err);
        this.loading = false;
        this.cdr.detectChanges();
      }
    });
  }
}
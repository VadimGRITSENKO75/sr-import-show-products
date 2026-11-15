import { Component, inject, OnInit } from '@angular/core';
import { AsyncPipe, NgFor, NgIf, DatePipe } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { Observable, switchMap } from 'rxjs';
import { ImportService } from '../../services/import.service';
import { ImportReport } from '../../models/import-report.model';

@Component({
  selector: 'app-import-report',
  standalone: true,
  imports: [NgIf, NgFor, AsyncPipe, RouterLink, MatCardModule, MatButtonModule, MatIconModule],
  template: `
    <div style="max-width: 800px; margin: 20px auto;">
      <button mat-button routerLink="/import">
        <mat-icon>arrow_back</mat-icon> Назад к импорту
      </button>

      <ng-container *ngIf="report$ | async as report">
        <mat-card>
          <mat-card-header>
            <mat-card-title>Отчёт об импорте</mat-card-title>
            <mat-card-subtitle>ID задачи: {{ report.taskId }}</mat-card-subtitle>
          </mat-card-header>
          
          <mat-card-content style="margin-top: 20px;">
            <p><strong>Статус:</strong> 
              <span [style.color]="report.errors.length ? 'orange' : 'green'">
                {{ report.status === 'done' ? 'Успешно' : 'Завершено с ошибками' }}
              </span>
            </p>
            <p><strong>Успешно импортировано строк:</strong> {{ report.imported }}</p>

            <div *ngIf="report.errors.length > 0">
              <p style="color: red;"><strong>Найдены ошибки ({{ report.errors.length }}):</strong></p>
              <ul style="color: red; background: #fff0f0; padding: 15px; border-radius: 4px; max-height: 300px; overflow-y: auto;">
                <li *ngFor="let e of report.errors">{{ e }}</li>
              </ul>
            </div>
            <p *ngIf="report.errors.length === 0" style="color: green;">
              <mat-icon style="vertical-align: middle;">check_circle</mat-icon> 
              Ошибок нет. Все строки успешно обработаны.
            </p>
          </mat-card-content>
        </mat-card>
      </ng-container>

      <!-- Если отчёт ещё не готов -->
      <div *ngIf="!(report$ | async)" style="text-align: center; margin-top: 50px;">
        <p>Загрузка отчёта...</p>
      </div>
    </div>
  `
})
export class ImportReportComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private importService = inject(ImportService);

  report$!: Observable<ImportReport>;

  ngOnInit() {
    this.report$ = this.route.paramMap.pipe(
      switchMap(params => this.importService.getImportStatus(params.get('taskId')!))
    );
  }
}
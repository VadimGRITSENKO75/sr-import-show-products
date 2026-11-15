import { Component, inject, OnInit } from '@angular/core';
import { AsyncPipe, NgIf } from '@angular/common';
import { RouterLink } from '@angular/router';
import { Store } from '@ngrx/store';
import { MatTableModule } from '@angular/material/table';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatButtonModule } from '@angular/material/button';
import { FormsModule } from '@angular/forms';
import { ProductsActions } from '../../store/products/products.actions';
import {
  selectAllProducts,
  selectProductsLoading,
  selectTotal,
} from '../../store/products/products.reducer';

@Component({
  selector: 'app-product-list',
  standalone: true,
  imports: [
    AsyncPipe,
    NgIf,
    RouterLink,
    MatTableModule,
    MatPaginatorModule,
    MatProgressSpinnerModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    FormsModule,
  ],
  template: `
    <mat-form-field>
      <mat-label>Поиск по названию</mat-label>
      <input matInput [(ngModel)]="searchName" id="search-input" (keyup.enter)="search()" placeholder="Поиск по названию" />
    </mat-form-field>
    <button mat-raised-button color="primary" (click)="search()">Найти</button>

    <div *ngIf="loading$ | async"><mat-spinner diameter="40"></mat-spinner></div>

    <table mat-table [dataSource]="(products$ | async) ?? []" *ngIf="!(loading$ | async)">
      <ng-container matColumnDef="name">
        <th mat-header-cell *matHeaderCellDef>Название</th>
        <td mat-cell *matCellDef="let p">
          <a [routerLink]="['/products', p.id]">{{ p.name }}</a>
        </td>
      </ng-container>
      <ng-container matColumnDef="price">
        <th mat-header-cell *matHeaderCellDef>Цена</th>
        <td mat-cell *matCellDef="let p">{{ p.price }}</td>
      </ng-container>
      <ng-container matColumnDef="discount">
        <th mat-header-cell *matHeaderCellDef>Скидка %</th>
        <td mat-cell *matCellDef="let p">{{ p.discount }}</td>
      </ng-container>
      <tr mat-header-row *matHeaderRowDef="displayedColumns"></tr>
      <tr mat-row *matRowDef="let row; columns: displayedColumns"></tr>
    </table>

    <mat-paginator
      [length]="(total$ | async) ?? 0"
      [pageSize]="limit"
      [pageSizeOptions]="[10, 20, 50]"
      (page)="onPageChange($event)"
    ></mat-paginator>
  `,
})
export class ProductListComponent implements OnInit {
  private store = inject(Store);
  products$ = this.store.select(selectAllProducts);
  loading$ = this.store.select(selectProductsLoading);
  total$ = this.store.select(selectTotal);

  displayedColumns = ['name', 'price', 'discount'];
  searchName = '';
  page = 1;
  limit = 20;

  ngOnInit() {
    this.load();
  }

  load() {
    this.store.dispatch(
      ProductsActions.loadProducts({
        page: this.page,
        limit: this.limit,
        filters: { name: this.searchName || undefined },
      })
    );
  }

  search() {
    this.page = 1;
    this.load();
  }

  onPageChange(event: PageEvent) {
    this.page = event.pageIndex + 1;
    this.limit = event.pageSize;
    this.load();
  }
}
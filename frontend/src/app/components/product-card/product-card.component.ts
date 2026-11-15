import { Component, inject, OnInit } from '@angular/core';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { NgFor, NgIf, AsyncPipe } from '@angular/common';
import { ProductService } from '../../services/product.service';
import { Product } from '../../models/product.model';
import { MatCardModule } from '@angular/material/card';
import { MatListModule } from '@angular/material/list';
import { Observable, switchMap } from 'rxjs';

@Component({
  selector: 'app-product-card',
  standalone: true,
  imports: [NgIf, NgFor, AsyncPipe, MatCardModule, MatListModule, RouterLink],
  template: `
    <a routerLink="/products">← Назад к списку</a>
    <mat-card *ngIf="product$ | async as product">
      <mat-card-header>
        <mat-card-title>{{ product.name }}</mat-card-title>
        <mat-card-subtitle>Код: {{ product.externalCode }}</mat-card-subtitle>
      </mat-card-header>
      <mat-card-content>
        <p>{{ product.description }}</p>
        <p>Цена: {{ product.price }} | Скидка: {{ product.discount }}%</p>

        <h3>Доп. атрибуты</h3>
        <mat-list>
          <mat-list-item *ngFor="let attr of product.attributes">
            {{ attr.key }}: {{ attr.value }}
          </mat-list-item>
        </mat-list>

        <h3>Изображения</h3>
        <div *ngFor="let img of product.images">
          <img [src]="'http://localhost:8080' + img.path" width="200" />
        </div>
      </mat-card-content>
    </mat-card>
  `,
})
export class ProductCardComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private productService = inject(ProductService);

  product$!: Observable<Product>;

  ngOnInit() {
    this.product$ = this.route.paramMap.pipe(
      switchMap((params) => this.productService.getProduct(Number(params.get('id'))))
    );
  }
}
import { Routes } from '@angular/router';

export const routes: Routes = [
  { path: '', redirectTo: 'products', pathMatch: 'full' },
  {
    path: 'products',
    loadComponent: () =>
      import('./components/product-list/product-list.component').then(
        (m) => m.ProductListComponent
      ),
  },
  {
    path: 'products/:id',
    loadComponent: () =>
      import('./components/product-card/product-card.component').then(
        (m) => m.ProductCardComponent
      ),
  },
  {
    path: 'import',
    loadComponent: () =>
      import('./components/import/import.component')
      .then((m) => m.ImportComponent)
      .catch((err) => {
        console.error('Ошибка загрузки ImportComponent:', err);
        throw err;
      }),
  },
{
    path: 'imports',
    loadComponent: () => import('./components/import-list/import-list.component').then((m) => m.ImportListComponent),
  },
  // Страница детального отчёта
  {
    path: 'import/report/:taskId',
    loadComponent: () => import('./components/import-report/import-report.component').then((m) => m.ImportReportComponent),
  },
];
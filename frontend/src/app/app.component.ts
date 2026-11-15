import { Component } from '@angular/core';
import { RouterLink, RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, RouterLink],
  template: `
    <nav>
      <a routerLink="/products">Список товаров</a> |
      <a routerLink="/import">Импорт</a>
    </nav>
    <router-outlet></router-outlet>
  `,
})
export class AppComponent {}
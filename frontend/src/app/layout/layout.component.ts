import { Component } from '@angular/core';

import { RouterOutlet, RouterModule, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-layout',
  standalone: true,
  imports: [RouterOutlet, RouterModule, CommonModule],
  templateUrl: './layout.component.html',
})
export class LayoutComponent {
  constructor(
    public authService: AuthService,
    private router: Router
  ) {}

  logout() {
    this.authService.cerrarSesion();
    this.router.navigate(['/login']);
  }

  get nombreUsuario(): string {
    const datos = this.authService.obtenerDatosUsuario();
    return datos?.nombre || '';
  }
}

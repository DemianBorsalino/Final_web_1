import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-perfil',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './perfil.component.html',
})
export class PerfilComponent {
  constructor(public authService: AuthService) {}

  get nombre(): string {
    return this.authService.obtenerDatosUsuario()?.nombre || 'Usuario';
  }
}
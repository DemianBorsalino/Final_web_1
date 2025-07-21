import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-perfil',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './perfil.component.html',
})
export class PerfilComponent {
  usuario: any = null;
  publicaciones: any[] = [];
  imagenesDisponibles: any[] = [];

  constructor(private http: HttpClient) {
    this.cargarPublicaciones();
  }

  ngOnInit() {
    this.obtenerUsuario();
    this.cargarPublicaciones();
    this.cargarImagenesDisponibles();
  }

  obtenerUsuario() {
    const token = localStorage.getItem('jwt');
    if (!token) return;

    const headers = { Authorization: 'Bearer ' + token };
    this.http.get<any>(environment.apiurl + 'perfil', { headers })
      .subscribe({
        next: res => this.usuario = res,
        error: err => console.error('Error al obtener perfil de usuario', err)
      });
  }

  cargarImagenesDisponibles() {
    this.http.get<any[]>(environment.apiurl + 'FotosPerfil')
      .subscribe({
        next: res => this.imagenesDisponibles = res,
        error: err => console.error('Error al cargar imágenes de perfil', err)
      });
}

  guardarCambios() {
    const token = localStorage.getItem('jwt');
    if (!token) return;

    const headers = {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json'
    };

    console.log('Enviando datos al servidor:', this.usuario);

    this.http.patch<any>(environment.apiurl + 'usuario', this.usuario, { headers })
      .subscribe({
        next: res => {
          console.log('Perfil actualizado:', res);
          alert('Perfil actualizado correctamente');
        },
        error: err => {
          console.error('Error al actualizar perfil', err);
          alert('Error al actualizar el perfil');
        }
      });
  }


  cargarPublicaciones() {
    const token = localStorage.getItem('jwt');
    if (!token) return;

    const headers = { Authorization: 'Bearer ' + token };
    this.http.get<any[]>(environment.apiurl + 'mispublicaciones', { headers })
      .subscribe({
        next: res => this.publicaciones = res,
        error: err => console.error('Error al obtener publicaciones del usuario', err)
      });
  }
}
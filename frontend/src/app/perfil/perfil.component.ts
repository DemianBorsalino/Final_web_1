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
  urlImagenPerfil: string | null = null;
  imagenSeleccionada: File | null = null;
  publicaciones: any[] = [];

  constructor(private http: HttpClient) {
    this.cargarPublicaciones();
  }

  ngOnInit() {
    this.obtenerUsuario();
    this.cargarPublicaciones();
  }

  seleccionarImagen(event: any) {
  const file = event.target.files[0];
  if (file) {
    this.imagenSeleccionada = file;
  }
}


  obtenerUsuario() {
    const token = localStorage.getItem('jwt');
    if (!token) return;

    const headers = { Authorization: 'Bearer ' + token };
    this.http.get<any>(environment.apiurl + 'perfil', { headers })
      .subscribe({
        next: res => {
          this.usuario = res;
          if (res.foto_perfil) {
            this.urlImagenPerfil = environment.apiurl + 'adicional/' + res.foto_perfil;
          } else {
            this.urlImagenPerfil = 'perfilPredeterminado';
          }
        },
        error: err => console.error('Error al obtener perfil de usuario', err)
      });
  }
  guardarCambios() {
    const token = localStorage.getItem('jwt');
    if (!token) return;

    const formData = new FormData();
    formData.append('nombre_completo', this.usuario.nombre_completo);
    formData.append('descripcion', this.usuario.descripcion || '');

    if (this.imagenSeleccionada) {
      formData.append('foto_perfil', this.imagenSeleccionada);
    }

    const headers = { 'Authorization': 'Bearer ' + token  };
    this.http.post<any>(environment.apiurl + 'usuario', formData, { headers })
      .subscribe({
        next: res => {
          console.log('Perfil actualizado:', res);
          alert('Perfil actualizado correctamente');
          this.obtenerUsuario();
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
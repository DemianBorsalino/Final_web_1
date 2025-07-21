import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class AuthService {

  private readonly TOKEN_KEY = 'jwt';

  constructor() {}

  guardarToken(token: string): void {
    localStorage.setItem(this.TOKEN_KEY, token);
  }

  obtenerToken(): string | null {
    return localStorage.getItem(this.TOKEN_KEY);
  }

  eliminarToken(): void {
    localStorage.removeItem(this.TOKEN_KEY);
  }

  estaAutenticado(): boolean {
    return this.obtenerToken() !== null;
  }

  cerrarSesion(): void {
    this.eliminarToken();
  }

  // Opcional: decodificar payload del JWT si querés acceder a datos como nombre/id
  obtenerDatosUsuario(): any {
    const token = this.obtenerToken();
    if (!token) return null;

    const payload = token.split('.')[1];
    try {
      return JSON.parse(atob(payload));
    } catch (e) {
      return null;
    }
  }

  tieneRolId(rolId: number): boolean {
    const datos = this.obtenerDatosUsuario();
    return Number(datos?.rol)=== rolId;
  }

  esAdmin(): boolean {
    return this.tieneRolId(2); // ID del rol admin
  }

  esUsuario(): boolean {
    return this.tieneRolId(1); // ID del rol usuario
  }

}
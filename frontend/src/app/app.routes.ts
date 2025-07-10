import { Routes } from '@angular/router';
import { LoginComponent } from './login/login.component';
import { AppComponent } from './app.component'; // si usás AppComponent como ruta principal

export const routes: Routes = [
  { path: 'login', component: LoginComponent },
  { path: '', component: AppComponent } // opcional: ruta raíz (home)
];
import { Routes } from '@angular/router';
import { LayoutComponent } from './layout/layout.component';
import { LoginComponent } from './login/login.component';
import { RegisterComponent } from './login/register.component';
import { HomeComponent } from './home/home.component';
import { PerfilComponent } from './perfil/perfil.component';
import { authGuard } from './guards/auth.guard';
import { PublicacionesComponent } from './publicaciones/publicaciones.component';
import { MisPublicacionesComponent } from './publicaciones/mis-publicaciones/mis-publicaciones.component';

export const routes: Routes = [
  {
    path: '',
    component: LayoutComponent,
    children: [
      { path: '', component: HomeComponent },
      { path: 'login', component: LoginComponent },
      { path: 'register', component: RegisterComponent },
      { path: 'perfil', component: PerfilComponent, canActivate: [authGuard] },
      { path: 'publicaciones', component: PublicacionesComponent },
      { path: 'mis-publicaciones', component: MisPublicacionesComponent, canActivate: [authGuard] }
    ]
  }
];
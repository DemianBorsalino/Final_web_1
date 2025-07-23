Proyecto Programacion web 1
Autor: Demián Renzo Borsalino

Requesitos para poder correr este proyecto:
  -Xammp (Apache y sql)
  -Angular
  -Navegador web

Desarrollo:

Esta página es una página de un foro al estilo Reddit. La idea es que alguien pueda hacer posteos o publicaciones donde otros o el mismo usario puede comentar al respecto. 
Utilizamos los contenidos vistos en clases como GET, DELETE, POST y PATCH. 
Para poder autenticar el usuario, fue importante el uso de tokens (jwt). Este permite identificar si el usuario está activo y luego de un tiempo se desactivará. Esto logrará que el usuario tenga volver a logear para poder seguir operando
Las funciones están implementadas en index.php, donde se encuentran funciones como getPerfil(),getPublicaciones(), deleteComentario(id), etc. 
En frontend/src va a haber varios elementos que representarían las distintas partes de la pagina, donde va estar el diseño y funcionalidades de cada parte del sitio (html para visual, ts para acceso de funciones de index.php)
En adicional está la base de datos que contiene los usuarios, publicaciones, comentarios y roles, además de las imagenes para las fotos de perfil.
Administradores pueden borrar los comentarios y publicaciones de todos los usarios, mientras el los usuarios pueden solo borrar los suyos. Ambos pueden editar sus propias publicaciones

Cómo usar:

Entrar a localhost:4200
Los botones llevaran a los distintos sitios de la página. Probar registrar distintos usuarios con distintos roles para ver funcionalidades

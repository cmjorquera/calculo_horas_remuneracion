# Manual de Usuario
## Calculadora de Horas Cronológicas

## 1. Introducción al sistema

La Calculadora de Horas Cronológicas es una plataforma orientada a la gestión administrativa de funcionarios docentes y su distribución horaria semanal. El sistema permite registrar horarios de trabajo, calcular resúmenes de jornada, revisar información consolidada, exportar datos y administrar usuarios internos.

Su uso principal se concentra en dos áreas:

- gestión de funcionarios y sus horarios
- administración de usuarios y permisos

El sistema está pensado para trabajar por colegio, por lo que cada usuario visualiza la información de acuerdo con su perfil y alcance.

## 2. Perfiles del sistema

### 2.1. Super admin

El perfil Super admin tiene acceso global al sistema.

Puede realizar las siguientes acciones:

- visualizar funcionarios de todos los colegios
- ver la columna de colegio en el listado principal
- crear funcionarios asignándolos a distintos colegios
- administrar usuarios del sistema
- administrar permisos de menú
- exportar información global

### 2.2. Administrador hora colegio

El perfil Administrador hora colegio trabaja solo con el colegio asociado a su cuenta.

Puede realizar las siguientes acciones:

- visualizar funcionarios de su colegio
- crear y editar funcionamientos del propio colegio
- revisar el listado de funcionarios de su establecimiento
- exportar información correspondiente a su colegio

No tiene acceso a la administración global de usuarios.

## 3. Pantalla principal del sistema

La pantalla principal es el centro de trabajo del sistema. Desde esta vista se realizan las tareas más importantes relacionadas con horarios y funcionarios.

En esta pantalla el usuario puede:

- cargar un horario semanal
- revisar el resumen de horas calculadas
- agregar un funcionario
- consultar el listado de funcionarios
- ejecutar acciones sobre registros ya creados
- exportar la información disponible

En términos prácticos, el flujo general es el siguiente:

1. Completar el horario semanal.
2. Revisar el resumen calculado por el sistema.
3. Registrar los datos del funcionario.
4. Guardar la información.
5. Revisar el funcionario en el listado principal.

## 4. Carga de horario semanal

La carga horaria semanal se realiza de lunes a viernes. Cada día permite registrar jornada de mañana y jornada de tarde.

Para cada día se pueden ingresar los siguientes datos:

- hora de inicio de mañana
- hora de término de mañana
- hora de inicio de tarde
- hora de término de tarde

Además, si un día no corresponde jornada laboral, puede marcarse como bloqueado o no laborable.

### Paso a paso para cargar un horario

1. Ingrese a la pantalla principal.
2. Ubique la sección superior de carga horaria.
3. Complete los horarios de lunes a viernes según corresponda.
4. Si un día no debe considerarse, márquelo como bloqueado.
5. Revise que los tramos queden correctamente distribuidos.
6. Observe el resumen automático generado por el sistema.

No es obligatorio completar todos los días, siempre que la configuración ingresada sea válida.

## 5. Validaciones del sistema

Durante la carga de información, el sistema realiza validaciones automáticas para evitar errores.

### 5.1. Validaciones del horario

El sistema controla, entre otras, las siguientes reglas:

- solo se trabaja con días hábiles de lunes a viernes
- la jornada de tarde no puede comenzar antes del término de la jornada de mañana
- los bloques horarios deben ser consistentes
- los datos inválidos impiden continuar o muestran advertencias

### 5.2. Validaciones del resumen

El sistema calcula automáticamente:

- horas cronológicas
- horas lectivas
- horas no lectivas
- colación

Además, informa si la jornada semanal cumple o no con las 40 horas legales. Cuando no se cumple, se muestra una advertencia visual.

### 5.3. Validaciones de datos del funcionario

Al guardar un funcionario, el sistema valida lo siguiente:

- que el RUN no exista previamente
- que el correo no exista previamente
- que los datos obligatorios estén completos

Si existe un conflicto, el sistema lo informa antes de guardar.

## 6. Creación de funcionario

La creación del funcionario se realiza como parte del flujo de carga del funcionamiento.

### Paso a paso para crear un funcionario

1. Complete el horario semanal.
2. Revise el resumen calculado.
3. Presione la opción para agregar funcionario.
4. Complete los datos solicitados en el formulario.
5. Guarde la información.

Los datos solicitados incluyen:

- nombre
- apellido paterno
- apellido materno
- RUN
- correo electrónico
- sexo
- teléfono
- observación

### Consideraciones según perfil

- si el usuario pertenece a un colegio específico, el funcionario se registra en ese mismo colegio
- si el usuario tiene perfil Super admin, puede seleccionar el colegio al que se asociará el funcionario

Al finalizar el proceso, el sistema muestra una confirmación y el funcionario queda disponible en el listado principal.

## 7. Listado de funcionarios

El listado principal muestra los funcionarios registrados en el sistema junto con su información resumida.

Las columnas visibles pueden incluir:

- ID
- RUN
- nombre y apellido
- colegio
- jornada cronológica
- colación
- horas no lectivas
- horas lectivas
- opciones

### Comportamiento del listado

- permite búsqueda y filtrado
- muestra los valores horarios en formato legible
- en vista global puede mostrar el colegio asociado

Este listado sirve tanto para consulta rápida como para ejecutar acciones sobre cada funcionario.

## 8. Funciones de los botones de acciones

Cada funcionario dispone de botones u opciones de acción dentro del listado principal.

### 8.1. Recargar funcionamiento

Permite volver a cargar en la parte superior de la pantalla el horario y el resumen de un funcionario ya registrado.

Se utiliza para:

- revisar un funcionamiento existente
- retomar información ya ingresada
- editar o ajustar la configuración horaria

### 8.2. Visualizar horario

Abre una vista detallada del horario del funcionario.

Desde esta visualización es posible:

- revisar el detalle diario del horario
- revisar el resumen asociado
- descargar el PDF del funcionamiento

### 8.3. Ver observación

Permite visualizar la observación registrada para el funcionario o su funcionamiento.

Esta opción es útil para revisar comentarios administrativos asociados al registro.

## 9. Exportación de información a Excel

El sistema permite descargar el listado de funcionarios en formato Excel desde la pantalla principal.

### Paso a paso

1. Ingrese a la pantalla principal.
2. Ubique el botón de exportación a Excel.
3. Presione la opción de descarga.
4. Espere la generación del archivo.
5. Abra el archivo descargado en Excel.

### Alcance de la exportación

- el Super admin puede exportar información global
- el Administrador hora colegio exporta solo la información de su colegio

La exportación replica el contenido principal del listado visible en pantalla.

## 10. Visualización del horario en PDF

El sistema permite generar un PDF con el detalle del funcionamiento de un funcionario.

### Paso a paso

1. Busque al funcionario en el listado.
2. Presione la opción `Visualizar horario`.
3. Revise la información desplegada.
4. Use la opción de descarga en PDF.

El documento PDF puede incluir:

- identificación del funcionario
- colegio
- resumen horario
- colación
- observación
- detalle diario del horario

Esta funcionalidad es útil para revisión, respaldo o entrega formal de información.

## 11. Administración de usuarios

La administración de usuarios está disponible para perfiles con acceso global, especialmente Super admin.

Desde este módulo es posible:

- crear usuarios internos
- asociarlos a un colegio
- asignarles rol
- controlar su estado
- revisar su información principal

### 11.1. Creación de usuario

Para crear un usuario:

1. Ingrese al módulo de usuarios.
2. Presione `Agregar usuario`.
3. Complete los datos solicitados.
4. Seleccione el colegio correspondiente.
5. Asigne el rol si corresponde.
6. Defina el estado inicial.
7. Guarde la información.

Los campos del formulario pueden incluir:

- nombre
- apellido paterno
- apellido materno
- colegio
- RUN
- teléfono
- estado

El sistema genera el identificador del usuario en forma automática y valida que el correo no exista previamente.

Al guardar, el sistema informa que se envió un correo para que el usuario defina su clave.

### 11.2. Estado del usuario

El sistema distingue entre usuarios activos, inactivos o pendientes de activación.

En la práctica:

- un usuario que aún no define su clave puede aparecer como pendiente
- el sistema puede mostrar un indicador visual informando que falta activar la cuenta
- una vez definida la clave, la cuenta queda habilitada para su uso normal

### 11.3. Recuperación de clave

Si un usuario olvida su contraseña, puede utilizar la opción `¿Olvidaste tu contraseña?` desde la pantalla de inicio de sesión.

### Paso a paso

1. Haga clic en `¿Olvidaste tu contraseña?`.
2. Ingrese su correo registrado.
3. Presione `Enviar enlace`.
4. Revise su correo electrónico.
5. Abra el enlace recibido.
6. Ingrese la nueva clave dos veces.
7. Guarde la nueva clave.

Validaciones del proceso:

- el correo debe existir en el sistema
- el enlace de recuperación debe estar vigente
- la nueva clave debe ingresarse dos veces
- ambas claves deben coincidir
- la nueva clave debe cumplir el largo mínimo exigido por el sistema

## 12. Administración de permisos por menú

La administración de permisos por menú permite definir qué secciones del sistema puede ver cada usuario.

Esta funcionalidad está destinada al perfil Super admin.

### Paso a paso

1. Ingrese al módulo de usuarios.
2. Busque el usuario que desea administrar.
3. Abra la opción de permisos.
4. Revise la lista de menús disponibles.
5. Active o desactive cada menú según corresponda.
6. Guarde los cambios si la interfaz lo solicita.

### Ejemplos de menús administrables

- Empleados
- Gráficos
- Usuarios

Esta funcionalidad permite adaptar la navegación del sistema según el rol y las necesidades de cada cuenta.

## 13. Recomendaciones de uso

Para un mejor uso del sistema:

1. Revise siempre los horarios antes de guardar.
2. Verifique el resumen calculado antes de crear un funcionario.
3. Mantenga actualizados los datos de correo de los usuarios.
4. Utilice la exportación a Excel para respaldos y revisiones administrativas.
5. Controle periódicamente los permisos de menú para evitar accesos no deseados.

## 14. Cierre

La Calculadora de Horas Cronológicas permite administrar horarios, funcionarios, usuarios y permisos desde una sola plataforma. Su uso correcto facilita el control administrativo, mejora la consulta de información y simplifica la gestión de jornadas docentes en cada colegio.

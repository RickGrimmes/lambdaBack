# 🔔 GUÍA COMPLETA DE NOTIFICACIONES - SISTEMA LAMBDA

Esta guía explica cómo implementar las notificaciones en el **frontend (Angular/Ionic PWA)** que se sincronizan con el **backend Laravel** ya configurado.

---

## 📋 ÍNDICE

1. [¿Qué está hecho en el Backend?](#-qué-está-hecho-en-el-backend)
2. [Endpoints Disponibles](#-endpoints-disponibles)
3. [Estructura de Datos](#-estructura-de-datos)
4. [Frontend - Servicio API](#-frontend---servicio-api)
5. [Frontend - Servicio de Estado](#-frontend---servicio-de-estado)
6. [Frontend - Página de Notificaciones](#-frontend---página-de-notificaciones)
7. [Frontend - Badge en Tabs](#-frontend---badge-en-tabs)
8. [Navegación según tipo](#-navegación-según-tipo)
9. [Checklist de Implementación](#-checklist-de-implementación)

---

## ✅ ¿QUÉ ESTÁ HECHO EN EL BACKEND?

### Base de Datos
- ✅ Tabla `notifications` creada con:
  - `id`, `user_id`, `type`, `title`, `body`, `data` (JSON), `read`, `read_at`, `created_at`, `updated_at`
  - Índices en `user_id` y `read` para consultas rápidas

### Tabla adicional: `push_subscriptions`
- ✅ Para soporte de WebPush (notificaciones push en navegador)
- Campos: `user_id`, `endpoint`, `public_key`, `auth_token`, `content_encoding`

### Modelo Notification
```php
class Notification extends Model
{
    // Relación con User
    public function user()
    
    // Método para marcar como leída
    public function markAsRead()
    
    // Scopes para consultas
    public function scopeUnread($query)  // Solo no leídas
    public function scopeForUser($query, $userId)  // De un usuario específico
}
```

### Controlador NotificationController
- ✅ 8 endpoints REST completamente funcionales
- ✅ Autenticación JWT en todos los endpoints
- ✅ Paginación en listado de notificaciones
- ✅ Validaciones y manejo de errores

### Creación Automática
- ✅ **Cuando un trainer crea un ejercicio** → Se envía notificación automáticamente a todos los trainees de esa sala
- ✅ Notificación se guarda en BD con todos los detalles
- ✅ Soporte multi-dispositivo (un usuario puede tener varias suscripciones)

---

## 🌐 ENDPOINTS DISPONIBLES

### Base URL
```
http://tu-dominio.com/api
```

### Autenticación
Todos los endpoints requieren header:
```
Authorization: Bearer {token_jwt}
```

---

### 1️⃣ Obtener mis notificaciones
```http
GET /api/notifications/my
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 3,
        "type": "new_exercise",
        "title": "🏋️ Nuevo ejercicio asignado",
        "body": "Tu entrenador ha añadido: Press de Banca",
        "data": {
          "type": "new_exercise",
          "exercise_id": 45,
          "room_id": 12,
          "room_name": "Rutina Avanzada"
        },
        "read": false,
        "read_at": null,
        "created_at": "2025-12-04T10:30:00.000000Z",
        "updated_at": "2025-12-04T10:30:00.000000Z"
      }
    ],
    "per_page": 20,
    "total": 15
  },
  "unread_count": 5
}
```

---

### 2️⃣ Obtener contador de no leídas
```http
GET /api/notifications/unread-count
```

**Respuesta:**
```json
{
  "success": true,
  "count": 5
}
```

**💡 Uso:** Para mostrar el badge en el tab de notificaciones

---

### 3️⃣ Marcar notificación como leída
```http
PUT /api/notifications/{id}/read
```

**Ejemplo:**
```http
PUT /api/notifications/1/read
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Notificación marcada como leída"
}
```

---

### 4️⃣ Marcar todas como leídas
```http
PUT /api/notifications/read-all
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Todas las notificaciones marcadas como leídas"
}
```

**💡 Uso:** Botón "Marcar todas como leídas" en la página de notificaciones

---

### 5️⃣ Eliminar notificación
```http
DELETE /api/notifications/{id}
```

**Ejemplo:**
```http
DELETE /api/notifications/1
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Notificación eliminada"
}
```

---

### 6️⃣ Suscribirse a notificaciones push (opcional)
```http
POST /api/notifications/subscribe
```

**Body:**
```json
{
  "endpoint": "https://fcm.googleapis.com/fcm/send/...",
  "keys": {
    "p256dh": "clave_publica_del_navegador",
    "auth": "token_autenticacion"
  }
}
```

---

### 7️⃣ Obtener clave pública VAPID (para WebPush)
```http
GET /api/vapid-public-key
```

**Respuesta:**
```json
{
  "success": true,
  "public_key": "BMDCq6F0HgVgMqHzPv0iJbxvIx9AJQtlgZYiP1nrxGt..."
}
```

---

### 8️⃣ Enviar notificación de prueba (solo para testing)
```http
POST /api/notifications/test
```

---

## 📦 ESTRUCTURA DE DATOS

### Objeto Notification

```typescript
interface Notification {
  id: number;
  user_id: number;
  type: 'new_exercise' | 'test' | string;
  title: string;
  body: string;
  data: {
    type: string;
    exercise_id?: number;
    room_id?: number;
    room_name?: string;
    [key: string]: any;
  };
  read: boolean;
  read_at: string | null;
  created_at: string;
  updated_at: string;
}
```

### Tipos de notificaciones actuales

| Tipo | Cuándo se crea | Data incluida |
|------|----------------|---------------|
| `new_exercise` | Trainer crea ejercicio | `exercise_id`, `room_id`, `room_name` |
| `test` | Endpoint de prueba | `timestamp` |

---

## 🔧 FRONTEND - SERVICIO API

### 1. Crear servicio

```bash
ng generate service services/notifications-api
```

### 2. Implementar servicio

**Archivo: `src/app/services/notifications-api.service.ts`**

```typescript
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface Notification {
  id: number;
  user_id: number;
  type: 'new_exercise' | 'test' | string;
  title: string;
  body: string;
  data: {
    type: string;
    exercise_id?: number;
    room_id?: number;
    room_name?: string;
    [key: string]: any;
  };
  read: boolean;
  read_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface NotificationsResponse {
  success: boolean;
  data: {
    current_page: number;
    data: Notification[];
    per_page: number;
    total: number;
  };
  unread_count: number;
}

export interface UnreadCountResponse {
  success: boolean;
  count: number;
}

@Injectable({
  providedIn: 'root'
})
export class NotificationsApiService {
  private apiUrl = `${environment.apiUrl}/notifications`;

  constructor(private http: HttpClient) {}

  /**
   * Obtener todas las notificaciones del usuario
   */
  getNotifications(): Observable<NotificationsResponse> {
    return this.http.get<NotificationsResponse>(`${this.apiUrl}/my`);
  }

  /**
   * Obtener contador de notificaciones no leídas
   */
  getUnreadCount(): Observable<UnreadCountResponse> {
    return this.http.get<UnreadCountResponse>(`${this.apiUrl}/unread-count`);
  }

  /**
   * Marcar una notificación como leída
   */
  markAsRead(id: number): Observable<any> {
    return this.http.put(`${this.apiUrl}/${id}/read`, {});
  }

  /**
   * Marcar todas las notificaciones como leídas
   */
  markAllAsRead(): Observable<any> {
    return this.http.put(`${this.apiUrl}/read-all`, {});
  }

  /**
   * Eliminar una notificación
   */
  deleteNotification(id: number): Observable<any> {
    return this.http.delete(`${this.apiUrl}/${id}`);
  }
}
```

---

## 🔄 FRONTEND - SERVICIO DE ESTADO

### 1. Crear servicio

```bash
ng generate service services/notification
```

### 2. Implementar servicio

**Archivo: `src/app/services/notification.service.ts`**

```typescript
import { Injectable, NgZone } from '@angular/core';
import { BehaviorSubject, Observable } from 'rxjs';
import { NotificationsApiService, Notification } from './notifications-api.service';
import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root'
})
export class NotificationService {
  // Observable de notificaciones
  private notificationsSubject = new BehaviorSubject<Notification[]>([]);
  public notifications$: Observable<Notification[]> = this.notificationsSubject.asObservable();
  
  // Observable de contador no leídas
  private unreadCountSubject = new BehaviorSubject<number>(0);
  public unreadCount$: Observable<number> = this.unreadCountSubject.asObservable();

  private syncInterval: any;
  private readonly SYNC_INTERVAL_MS = 30000; // 30 segundos

  constructor(
    private notificationsApi: NotificationsApiService,
    private authService: AuthService,
    private ngZone: NgZone
  ) {
    this.initAutoSync();
  }

  /**
   * Inicializar sincronización automática cada 30 segundos
   */
  private initAutoSync(): void {
    // Sincronizar inmediatamente si está autenticado
    if (this.authService.isAuthenticated()) {
      this.syncNotificationsFromBackend();
    }

    // Configurar intervalo de sincronización
    this.ngZone.runOutsideAngular(() => {
      this.syncInterval = setInterval(() => {
        if (this.authService.isAuthenticated()) {
          this.ngZone.run(() => {
            this.syncNotificationsFromBackend();
          });
        }
      }, this.SYNC_INTERVAL_MS);
    });
  }

  /**
   * Sincronizar notificaciones desde el backend
   */
  syncNotificationsFromBackend(): void {
    if (!this.authService.isAuthenticated()) {
      this.notificationsSubject.next([]);
      this.unreadCountSubject.next(0);
      return;
    }

    this.notificationsApi.getNotifications().subscribe({
      next: (response) => {
        if (response.success) {
          console.log('[NOTIFICATIONS] Sincronizadas:', response.data.data.length);
          this.notificationsSubject.next(response.data.data);
          this.unreadCountSubject.next(response.unread_count);
        }
      },
      error: (error) => {
        console.error('[NOTIFICATIONS] Error al sincronizar:', error);
      }
    });
  }

  /**
   * Marcar notificación como leída
   */
  markAsRead(id: number): void {
    this.notificationsApi.markAsRead(id).subscribe({
      next: () => {
        // Actualizar localmente
        const notifications = this.notificationsSubject.value;
        const updated = notifications.map(n => 
          n.id === id ? { ...n, read: true, read_at: new Date().toISOString() } : n
        );
        this.notificationsSubject.next(updated);
        
        // Actualizar contador
        const unreadCount = updated.filter(n => !n.read).length;
        this.unreadCountSubject.next(unreadCount);
      },
      error: (error) => {
        console.error('[NOTIFICATIONS] Error al marcar como leída:', error);
      }
    });
  }

  /**
   * Marcar todas como leídas
   */
  markAllAsRead(): void {
    this.notificationsApi.markAllAsRead().subscribe({
      next: () => {
        // Actualizar localmente
        const notifications = this.notificationsSubject.value;
        const updated = notifications.map(n => ({ 
          ...n, 
          read: true, 
          read_at: new Date().toISOString() 
        }));
        this.notificationsSubject.next(updated);
        this.unreadCountSubject.next(0);
      },
      error: (error) => {
        console.error('[NOTIFICATIONS] Error al marcar todas:', error);
      }
    });
  }

  /**
   * Eliminar notificación
   */
  deleteNotification(id: number): void {
    this.notificationsApi.deleteNotification(id).subscribe({
      next: () => {
        // Eliminar localmente
        const notifications = this.notificationsSubject.value;
        const updated = notifications.filter(n => n.id !== id);
        this.notificationsSubject.next(updated);
        
        // Actualizar contador
        const unreadCount = updated.filter(n => !n.read).length;
        this.unreadCountSubject.next(unreadCount);
      },
      error: (error) => {
        console.error('[NOTIFICATIONS] Error al eliminar:', error);
      }
    });
  }

  /**
   * Limpiar notificaciones (al cerrar sesión)
   */
  clear(): void {
    this.notificationsSubject.next([]);
    this.unreadCountSubject.next(0);
    
    if (this.syncInterval) {
      clearInterval(this.syncInterval);
    }
  }

  /**
   * Destruir servicio
   */
  ngOnDestroy(): void {
    if (this.syncInterval) {
      clearInterval(this.syncInterval);
    }
  }
}
```

---

## 📱 FRONTEND - PÁGINA DE NOTIFICACIONES

### 1. Crear página

```bash
ionic generate page pages/notifications
```

### 2. Implementar TypeScript

**Archivo: `notifications.page.ts`**

```typescript
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { NotificationService } from '../../services/notification.service';
import { Notification } from '../../services/notifications-api.service';
import { Observable } from 'rxjs';
import { AlertController } from '@ionic/angular';

@Component({
  selector: 'app-notifications',
  templateUrl: './notifications.page.html',
  styleUrls: ['./notifications.page.scss'],
})
export class NotificationsPage implements OnInit {
  notifications$: Observable<Notification[]>;
  unreadCount$: Observable<number>;

  constructor(
    private notificationService: NotificationService,
    private router: Router,
    private alertController: AlertController
  ) {
    this.notifications$ = this.notificationService.notifications$;
    this.unreadCount$ = this.notificationService.unreadCount$;
  }

  ngOnInit() {
    // Sincronizar al entrar a la página
    this.notificationService.syncNotificationsFromBackend();
  }

  /**
   * Manejar clic en una notificación
   */
  handleNotificationClick(notification: Notification) {
    // Marcar como leída si no lo está
    if (!notification.read) {
      this.notificationService.markAsRead(notification.id);
    }

    // Navegar según el tipo
    switch (notification.type) {
      case 'new_exercise':
        // Navegar al ejercicio específico
        this.router.navigate(['/tabs/excercise', notification.data.exercise_id]);
        break;
      
      case 'test':
        // Notificación de prueba, no hacer nada
        break;
      
      default:
        console.log('Tipo de notificación desconocido:', notification.type);
    }
  }

  /**
   * Marcar todas como leídas
   */
  async markAllAsRead() {
    const alert = await this.alertController.create({
      header: 'Marcar todas como leídas',
      message: '¿Estás seguro de marcar todas las notificaciones como leídas?',
      buttons: [
        {
          text: 'Cancelar',
          role: 'cancel'
        },
        {
          text: 'Sí, marcar todas',
          handler: () => {
            this.notificationService.markAllAsRead();
          }
        }
      ]
    });

    await alert.present();
  }

  /**
   * Eliminar notificación
   */
  async deleteNotification(notification: Notification, event: Event) {
    event.stopPropagation(); // Evitar que se dispare el click de la notificación

    const alert = await this.alertController.create({
      header: 'Eliminar notificación',
      message: '¿Estás seguro de eliminar esta notificación?',
      buttons: [
        {
          text: 'Cancelar',
          role: 'cancel'
        },
        {
          text: 'Eliminar',
          role: 'destructive',
          handler: () => {
            this.notificationService.deleteNotification(notification.id);
          }
        }
      ]
    });

    await alert.present();
  }

  /**
   * Formatear fecha relativa (hace 5 minutos, hace 1 hora, etc.)
   */
  getRelativeTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Ahora';
    if (diffMins < 60) return `Hace ${diffMins} min`;
    if (diffHours < 24) return `Hace ${diffHours} hora${diffHours > 1 ? 's' : ''}`;
    if (diffDays < 7) return `Hace ${diffDays} día${diffDays > 1 ? 's' : ''}`;
    
    return date.toLocaleDateString();
  }
}
```

### 3. Implementar HTML

**Archivo: `notifications.page.html`**

```html
<ion-header>
  <ion-toolbar>
    <ion-buttons slot="start">
      <ion-back-button defaultHref="/tabs/home"></ion-back-button>
    </ion-buttons>
    <ion-title>
      Notificaciones
      <ion-badge *ngIf="(unreadCount$ | async) > 0" color="danger">
        {{ unreadCount$ | async }}
      </ion-badge>
    </ion-title>
    <ion-buttons slot="end">
      <ion-button (click)="markAllAsRead()">
        <ion-icon slot="icon-only" name="checkmark-done"></ion-icon>
      </ion-button>
    </ion-buttons>
  </ion-toolbar>
</ion-header>

<ion-content>
  <div *ngIf="(notifications$ | async)?.length === 0" class="empty-state">
    <ion-icon name="notifications-off-outline"></ion-icon>
    <h3>No tienes notificaciones</h3>
    <p>Cuando recibas notificaciones aparecerán aquí</p>
  </div>

  <ion-list *ngIf="(notifications$ | async)?.length > 0">
    <ion-item-sliding *ngFor="let notif of notifications$ | async">
      
      <!-- Notificación -->
      <ion-item 
        [class.unread]="!notif.read"
        (click)="handleNotificationClick(notif)"
        button
        detail="false">
        
        <ion-icon 
          slot="start" 
          [name]="notif.read ? 'mail-open-outline' : 'mail-unread-outline'"
          [color]="notif.read ? 'medium' : 'primary'">
        </ion-icon>
        
        <ion-label>
          <h2>{{ notif.title }}</h2>
          <p>{{ notif.body }}</p>
          <p class="timestamp">{{ getRelativeTime(notif.created_at) }}</p>
        </ion-label>
        
        <ion-badge 
          *ngIf="!notif.read" 
          slot="end" 
          color="primary">
          Nuevo
        </ion-badge>
      </ion-item>

      <!-- Opciones deslizar -->
      <ion-item-options side="end">
        <ion-item-option color="danger" (click)="deleteNotification(notif, $event)">
          <ion-icon slot="icon-only" name="trash"></ion-icon>
        </ion-item-option>
      </ion-item-options>
      
    </ion-item-sliding>
  </ion-list>
</ion-content>
```

### 4. Implementar SCSS

**Archivo: `notifications.page.scss`**

```scss
ion-item.unread {
  --background: var(--ion-color-primary-tint);
  font-weight: 600;
  
  ion-label h2 {
    font-weight: 700;
  }
}

.timestamp {
  font-size: 0.75rem;
  color: var(--ion-color-medium);
  margin-top: 4px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 70vh;
  text-align: center;
  padding: 20px;
  
  ion-icon {
    font-size: 80px;
    color: var(--ion-color-medium);
    margin-bottom: 20px;
  }
  
  h3 {
    color: var(--ion-color-dark);
    margin-bottom: 10px;
  }
  
  p {
    color: var(--ion-color-medium);
    font-size: 14px;
  }
}

ion-badge {
  margin-left: 8px;
}
```

---

## 🔔 FRONTEND - BADGE EN TABS

### Modificar archivo de tabs

**Archivo: `tabs.page.html`** (o donde tengas tus tabs)

```html
<ion-tabs>
  <ion-tab-bar slot="bottom">
    
    <ion-tab-button tab="home">
      <ion-icon name="home"></ion-icon>
      <ion-label>Inicio</ion-label>
    </ion-tab-button>

    <ion-tab-button tab="notifications">
      <ion-icon name="notifications"></ion-icon>
      <ion-label>Notificaciones</ion-label>
      <!-- Badge con contador -->
      <ion-badge 
        *ngIf="(unreadCount$ | async) > 0" 
        color="danger">
        {{ unreadCount$ | async }}
      </ion-badge>
    </ion-tab-button>

    <ion-tab-button tab="profile">
      <ion-icon name="person"></ion-icon>
      <ion-label>Perfil</ion-label>
    </ion-tab-button>
    
  </ion-tab-bar>
</ion-tabs>
```

### Modificar TypeScript de tabs

**Archivo: `tabs.page.ts`**

```typescript
import { Component } from '@angular/core';
import { NotificationService } from '../services/notification.service';
import { Observable } from 'rxjs';

@Component({
  selector: 'app-tabs',
  templateUrl: 'tabs.page.html',
  styleUrls: ['tabs.page.scss']
})
export class TabsPage {
  unreadCount$: Observable<number>;

  constructor(private notificationService: NotificationService) {
    this.unreadCount$ = this.notificationService.unreadCount$;
  }
}
```

---

## 🧭 NAVEGACIÓN SEGÚN TIPO

### Configurar rutas en app-routing.module.ts

```typescript
const routes: Routes = [
  {
    path: 'tabs',
    component: TabsPage,
    children: [
      {
        path: 'notifications',
        loadChildren: () => import('./pages/notifications/notifications.module').then(m => m.NotificationsPageModule)
      },
      {
        path: 'excercise/:id',
        loadChildren: () => import('./pages/excercise-detail/excercise-detail.module').then(m => m.ExcerciseDetailPageModule)
      }
    ]
  }
];
```

### Tipos de navegación implementados

| Tipo de notificación | Acción al hacer clic |
|----------------------|---------------------|
| `new_exercise` | Navega a `/tabs/excercise/{exercise_id}` |
| `test` | No hace nada (solo para pruebas) |

**Puedes agregar más tipos según necesites:**

```typescript
switch (notification.type) {
  case 'new_exercise':
    this.router.navigate(['/tabs/excercise', notification.data.exercise_id]);
    break;
  
  case 'room_invitation':
    this.router.navigate(['/tabs/rooms', notification.data.room_id]);
    break;
    
  case 'routine_completed':
    this.router.navigate(['/tabs/routines']);
    break;
}
```

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

### Backend (Ya completado ✅)
- [x] Migración `notifications` creada
- [x] Migración `push_subscriptions` creada
- [x] Modelo `Notification` con scopes
- [x] Modelo `PushSubscription`
- [x] Controlador `NotificationController` con 8 endpoints
- [x] Rutas protegidas con JWT
- [x] Creación automática al crear ejercicio
- [x] WebPushService configurado

### Frontend (Por hacer)
- [ ] Crear servicio `notifications-api.service.ts`
- [ ] Crear servicio `notification.service.ts`
- [ ] Crear página de notificaciones
- [ ] Agregar badge en tabs
- [ ] Configurar navegación según tipo
- [ ] Integrar en el ciclo de vida de login/logout
- [ ] (Opcional) Implementar WebPush en PWA

---

## 🧪 PRUEBAS

### 1. Probar endpoints con Postman/Thunder Client

**GET Notificaciones:**
```
GET http://localhost:8000/api/notifications/my
Authorization: Bearer {tu_token}
```

**GET Contador:**
```
GET http://localhost:8000/api/notifications/unread-count
Authorization: Bearer {tu_token}
```

### 2. Crear notificación de prueba (Backend)

Desde Tinker:
```bash
php artisan tinker
```

```php
App\Models\Notification::create([
    'user_id' => 3, // ID de tu usuario
    'type' => 'test',
    'title' => '🧪 Prueba de notificación',
    'body' => 'Esta es una notificación de prueba desde Tinker',
    'data' => json_encode(['timestamp' => now()])
]);
```

### 3. Probar creación automática

1. Inicia sesión como **trainer**
2. Crea un **ejercicio** en una sala que tenga trainees
3. Inicia sesión como **trainee** de esa sala
4. Ve a `/api/notifications/my` → Deberías ver la notificación

---

## 🚀 INTEGRACIÓN EN APP

### En el AppComponent o servicio de autenticación

```typescript
// Al hacer login
this.authService.login(credentials).subscribe({
  next: (response) => {
    // Guardar token
    this.storage.set('token', response.token);
    
    // Iniciar sincronización de notificaciones
    this.notificationService.syncNotificationsFromBackend();
    
    this.router.navigate(['/tabs/home']);
  }
});

// Al hacer logout
logout() {
  this.storage.remove('token');
  
  // Limpiar notificaciones
  this.notificationService.clear();
  
  this.router.navigate(['/login']);
}
```

---

## 📝 NOTAS IMPORTANTES

### Sincronización automática
- Las notificaciones se sincronizan **cada 30 segundos** automáticamente
- También se sincronizan al entrar a la página de notificaciones
- Puedes cambiar el intervalo en `SYNC_INTERVAL_MS`

### Optimización
- Las notificaciones se guardan en un `BehaviorSubject` (estado local)
- No se hace petición al backend en cada renderizado
- Solo se consulta cuando es necesario

### WebPush (Opcional)
- El backend ya tiene soporte para WebPush
- Requiere HTTPS en producción (funciona en localhost sin SSL)
- Necesitas implementar Service Worker en el frontend
- Puedes usar la página de prueba en: `http://localhost:8000/test-push.html`

---

## 🆘 SOPORTE

Si tienes dudas sobre:
- **Backend:** Contacta al equipo backend
- **Estructura de datos:** Revisa la sección "Estructura de Datos"
- **Endpoints:** Revisa la sección "Endpoints Disponibles"

---

## 🎯 FLUJO COMPLETO

```
1. Trainer crea ejercicio
   ↓
2. Backend crea notificación automáticamente
   ↓
3. Notificación se guarda en BD
   ↓
4. Frontend sincroniza cada 30s
   ↓
5. Badge se actualiza automáticamente
   ↓
6. Trainee ve notificación en lista
   ↓
7. Trainee hace clic → Navega al ejercicio
   ↓
8. Notificación se marca como leída
```

---

**¡Sistema de notificaciones completo y listo para integrar! 🚀**

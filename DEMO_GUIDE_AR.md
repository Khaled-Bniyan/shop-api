# دليل تشغيل وعرض المشروع

هذا الدليل مخصص لتجهيز المشروع وعرض المتطلبين أمام المعيد. نفّذ الأوامر من
مجلد المشروع `shop-api` في PowerShell.

## التجهيز لأول مرة فقط

```powershell
Copy-Item .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate --force
docker compose exec app php artisan jwt:secret --force
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

إذا كان ملف `.env` موجوداً وفيه `APP_KEY` و`JWT_SECRET` فلا تعِد تنفيذ أوامر
توليد المفاتيح.

## التحضير قبل العرض

```powershell
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
docker compose exec inventory-service python manage_stock.py reset-demo
docker compose ps
```

استخدم `reset-demo` فقط قبل العرض عندما لا توجد حجوزات حقيقية قيد الاستخدام.
يجب أن تظهر الخدمات عاملة، وأن تظهر قواعد البيانات وخدمة المخزون بحالة
`healthy`.

افتح الواجهة من:

```text
http://localhost:8000
```

حسابات العرض:

| الدور | البريد | كلمة المرور |
|---|---|---|
| مسؤول | `admin@shop.test` | `password123` |
| زبون A | `a@shop.test` | `password123` |
| زبون B | `b@shop.test` | `password123` |

## سيناريو عرض المتطلب الأول

1. سجّل الدخول كزبون، واعرض المنتجات والتصنيفات وتقسيم الصفحات.
2. وضّح أن منتج «مصباح مكتبي ذكي» هو المنتج الوحيد المزروع بصورة، وأن بقية
   المنتجات بلا صور.
3. أضف منتجاً إلى السلة وأنشئ طلباً، ثم اعرض السعر المحسوب من الخادم وحالة
   الطلب.
4. سجّل الدخول كمسؤول، وأضف منتجاً جديداً مع صورة اختيارية، ثم غيّر حالة طلب
   الزبون.
5. ارجع إلى حساب الزبون واعرض إشعار المنتج الجديد وإشعار تغير حالة الطلب.
6. حاول إنشاء منتج أو تغيير حالة الطلب من حساب الزبون لإظهار `403`.
7. شغّل مجموعة Postman بالترتيب لإظهار JWT، الصلاحيات، الأخطاء الموحدة،
   ترويسات الأمان، والتحقق من البيانات. شغّل مجلد تحديد محاولات الدخول في
   النهاية حتى لا يؤثر مؤقتاً في بقية تسجيلات الدخول.

## سيناريو عرض المتطلب الثاني

1. اعرض الحاويات لتوضيح استقلال Laravel/MySQL عن Python/PostgreSQL:

   ```powershell
   docker compose ps
   ```

2. اعرض الكميات الموجودة في قاعدة المخزون المستقلة:

   ```powershell
   docker compose exec inventory-service python manage_stock.py list
   ```

3. افتح المنتجات في الواجهة، ووضّح أن `available_quantity` يأتي من عملية
   `GetStock` عبر gRPC.
4. أنشئ طلباً، ثم نفّذ أمر `list` مرة أخرى لتوضيح انخفاض الكمية بعد
   `ReserveStock`.
5. ألغِ الطلب من حساب المسؤول، ثم نفّذ `list` لإظهار رجوع الكمية بواسطة
   `ReleaseReservation`.
6. جرّب طلب كمية أكبر من المتاح من Postman؛ يجب أن تحصل على `422` وألا يُنشأ
   الطلب.
7. أثبت معالجة توقف الخدمة والمهلة:

   ```powershell
   docker compose stop inventory-service
   ```

   افتح تفاصيل منتج أو نفّذ طلب التفاصيل من Postman؛ يجب أن تحصل على `503`
   خلال مهلة محدودة وبصيغة الخطأ الموحدة. أعد الخدمة مباشرة:

   ```powershell
   docker compose start inventory-service
   docker compose ps inventory-service
   ```

8. أثبت معالجة سباق الحجز على آخر قطعة:

   ```powershell
   docker compose exec inventory-service python demo_concurrent_reservation.py
   ```

   النتيجة المطلوبة: حجز واحد `RESERVED` وطلب واحد
   `INSUFFICIENT_STOCK`، ثم تعاد القطعة تلقائياً.

9. شغّل الاختبارات الآلية:

   ```powershell
   docker compose exec app php artisan test
   ```

## إضافة منتج وكمية يدوياً

1. سجّل الدخول كمسؤول من الواجهة.
2. افتح «إضافة منتج»، اختر التصنيف والسعر والصورة الاختيارية، ثم احفظ.
3. اعرف رقم المنتج من قائمة المنتجات أو من استجابة طلب `POST /api/v1/products`.
   المنتج الجديد يظهر بكمية صفر لأن مخزونه مستقل.
4. عيّن كمية المنتج، مثلاً للمنتج رقم `109`:

   ```powershell
   docker compose exec inventory-service python manage_stock.py set 109 20
   ```

5. لإضافة خمس قطع إلى الكمية الحالية بدلاً من استبدالها:

   ```powershell
   docker compose exec inventory-service python manage_stock.py add 109 5
   ```

6. تحقق من النتيجة ثم حدّث صفحة المنتجات:

   ```powershell
   docker compose exec inventory-service python manage_stock.py list
   ```

`set` يضع الكمية النهائية، بينما `add` يزيد الكمية الحالية. يجب أن يكون رقم
المنتج هو الرقم نفسه الموجود في قاعدة Laravel.

## حل سريع للمشكلات

```powershell
docker compose ps
docker compose logs --tail=100 app
docker compose logs --tail=100 inventory-service
docker compose logs --tail=100 nginx
```

إذا ظهر `502` فتأكد أن `app` و`nginx` يعملان. إذا ظهر `503` في المنتجات فتأكد
أن `inventory-service` بحالة `healthy`.

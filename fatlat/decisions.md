# Kararlar

| No | Karar | Gerekçe |
|---|---|---|
| V-01 | Resmi Vito 4.x'in fork'u; fatihalp/vito temel alınmaz | fatihalp fork'u resmi testlerin neredeyse tamamını silmiş, kullanılan özellikleri (terminal, şifre sıfırlama, kullanıcı seçimi) kaldırmış; değişiklikleri yorum/izin değişiklikleriyle iç içe |
| V-02 | Seçilen özellikler yeniden yazılır, her biri ayrı commit ve testli | Resmi Vito'dan gelen güncellemeler sorunsuz birleşsin |
| V-03 | Kod stili resmi Vito'nunki (docblock'lar dahil) | plaka-takip'in "yorum yok" kuralı burada uygulanmaz; uygulanırsa her senkronda çakışma çıkar |
| V-04 | İmaj GitHub Actions'ta derlenir, `ghcr.io/fatlat/vito`'ya **gizli** paket olarak yüklenir | Geliştirme ortamında Packagist/npm erişimi yok; imaj herkese açık yayımlanmaz |
| V-05 | `public/build` repoda güncellenmez; imaj derlenirken yeniden üretilir | Derleme çıktısı commit'leri gürültü ve senkron çakışması üretir |
| V-06 | Yalnızca `linux/amd64` | Vito tek bir x64 Windows bilgisayarda çalışıyor |
| V-07 | Arayüz dili İngilizce | Türkçe çeviri seçilmedi |
| V-08 | Komut geçmişi yalnızca admin'e; davet araması yalnızca tam e-posta eşleşmesi | fatihalp fork'unda salt okuma yetkisiyle tüm `.bash_history` dosyaları okunabiliyor ve tüm kullanıcılar listelenebiliyordu |
| V-09 | Vito konteyneri `127.0.0.1:8090`'a bağlanır | Yerel ağa açık olmasına gerek yok |
| V-10 | Sunucu geneli limitler sayfası (16) yapılmaz | Resmi Vito 4.1'de site → Settings → PHP settings aynı ayarları (yükleme, süre, bellek, input vars ve Nginx `client_max_body_size`) site bazında yapıyor |
| V-11 | Yedek sağlığında yalnızca gecikme ve düzelme bildirilir | Başarısız yedek için resmi Vito zaten bildirim gönderiyor; Vito bilgisayarda çalıştığı için kaçırılan zamanlanmış yedekler asıl risk |
| V-12 | Boş `.env` kaydı engellenmez | Resmi Vito klasik modda boş kaydetmeyi bilerek destekliyor; her kayıttan önce önceki içerik saklandığı için yanlışlıkla boşaltılan dosya geri yüklenebiliyor |
| V-13 | Site kurulum ilerlemesi (11) yapılmaz | Resmi Vito 4.1 kurulum adımını gösteriyor ve "Retry installation" ile tamamlanan adımları atlayarak devam ediyor |
| V-14 | `.env` sürümleri veritabanında şifreli (`encrypted` cast) saklanır, yalnızca `.env`'yi görme yetkisi olanlara listelenir, liste içerik döndürmez | Sürümler gizli anahtarlar içeriyor |
| V-15 | Tek tıkla kurulum ayrı site türü değil, Laravel türüne seçenek olarak eklendi (varsayılan açık) | Ayrı tür, Laravel'e bağlı özellikleri (Modern Deployment, hazır deploy betiği, artisan komutları) kaybederdi |
| V-16 | Veritabanı ve kullanıcı adı site kullanıcısından türetilir, şifre rastgele 32 karakter; kullanıcı Vito'nun "veritabanıyla birlikte oluştur" yoluyla admin yetkisiyle bağlanır | Ayrı oluşturup bağlamamak 2026-10-04'teki "permission denied for schema public" hatasının sebebiydi |
| V-17 | Uygulama klasörü web dizininden türetilir (`panel/public` → `panel`) | Ek form alanı gerekmez |
| V-18 | Güvenlik puanı (18) yapılmaz | Resmi Vito 4.1 Security sayfasında puan, parola girişi, root girişi, Fail2ban, güvenlik duvarı ve otomatik güncelleme kartları var |
| V-19 | Süreçler, komut geçmişi, süreç sonlandırma ve log temizleme yalnızca admin/owner (`manage`); genel bakış ve güncellemeler her proje üyesine açık | Süreç komut satırları ve shell geçmişi parola içerebilir; admin zaten root terminali (Console) açabildiği için owner'a daraltmak ek koruma sağlamaz |
| V-20 | Log temizleme yalnızca `/var/log` altındaki yollar için; `..` ve kabuk karakterleri reddedilir, sunucuda `realpath` ile çözülen yol da `/var/log` içinde olmalı | Yol SSH komutuna giriyor; `/var/log` içindeki bir symlink başka bir dosyayı boşaltmasın |
| V-21 | Projeye göre gruplama ve özelleştirilebilir genel bakış kutucukları yapılmaz | Tek proje kullanılıyor; kod büyür, fayda yok |
| V-22 | Sayfa boyutu tablo sınıflarına dokunmadan `ApplyPageSize` ara katmanıyla `web.pagination_size` üzerinden uygulanır; izinli boyutlar `web.pagination_sizes`'ta, arayüze bootstrap ile gider | 20'den fazla tablo sınıfı değişmez, resmi Vito ile birleştirmelerde çakışma çıkmaz |
| V-23 | Davet için kullanıcı araması yapılmaz; davet e-posta ile, hızlı hesap oluşturma yalnızca uygulama admin'ine açık ve yeni hesap her zaman uygulama düzeyinde normal kullanıcı | Arama sistemdeki kullanıcıları ifşa ederdi; proje admin'inin uygulama admin'i açabilmesi yetki yükseltmesi olurdu |

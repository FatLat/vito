# Plan

fatihalp/vito fork'u incelendi (2026-10-05). Seçilen özellikler oradan kopyalanmaz; örnek alınarak resmi Vito'nun üzerine, Vito'nun kod stiliyle ve testleriyle yeniden yazılır. Numaralar seçim listesindeki numaralardır.

| Aşama | İçerik |
|---|---|
| F0 – Altyapı | Fork, gizli imaj (GHCR), bilgisayarda güncelleme betiği, plan dokümanları |
| F1 – Hızlı kazanımlar | 6 renkli loglar · 12 cron/worker şablonları · 16 PHP/Nginx limitleri sayfası · 24 rol/izin tablosu · 25 yedek sağlık uyarıları |
| F2 – Deploy ve .env | 8 deploy geçmişi (kim deploy etti / geri aldı, detay sayfası, log indirme) · 9 `.env` sürüm geçmişi, geri alma, boş `.env` koruması, `APP_DEBUG` uyarısı · 11 site kurulum ilerlemesi (adımlar, yeniden dene, iptal) |
| F3 – Tek tıkla Laravel | 27 "Laravel (hazır kurulum)" site türü: veritabanı + sahiplik, `.env`, deploy betiği, kuyruk işçisi, cron |
| F4 – Sunucu sayfaları | 15 izleme (süreçler, disk, bilgi, log boyutları) · 17 yeniden başlatma ve güncelleme sayfaları · 18 güvenlik puanı ve sertleştirme kartları · 19 komut geçmişi (yalnızca admin) |
| F5 – Gezinme ve tablolar | 1 yeni ana menü · 3 sunucu menüsü grupları · 4 site sekmeleri · 5 tablo iyileştirmeleri · 2 genel bakış ekranı |
| F6 – Projeler | 23 davetler ve hızlı kullanıcı oluşturma (arama yalnızca tam e-posta eşleşmesi) |

Her aşamanın sonunda imaj çıkar, Latif dener, sonra sonraki aşamaya geçilir.

## Korunanlar (fork'ta kaldırılmış ama burada kalan)
Cmd+K arama, site kurulumunda kullanıcı seçimi, canlı terminal, şifremi unuttum, Workflows, Scripts, WordPress site türü, veritabanı silme, dokümantasyon bağlantıları, resmi testler.

## Alınmayanlar
7, 10, 13, 14, 20, 21, 22, 26, 28.

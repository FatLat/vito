# Görevler

Sahip: **L** Latif · **C** Claude. Durum: ☐ bekliyor · ◐ sürüyor · ☑ bitti

## F0 — Altyapı
| No | Görev | Sahip | Durum |
|---|---|---|---|
| V-001 | FatLat/vito fork'u | L | ☑ |
| V-002 | Plan dokümanları (`fatlat/`) | C | ☑ |
| V-003 | `fatlat-image` workflow'u: testler geçince imajı derleyip gizli GHCR paketine yükle | C | ☑ |
| V-004 | Fork'ta GitHub Actions'ı aç; FatLat paket ayarında yalnızca gizli paket | L | ☑ |
| V-005 | `read:packages` token'ı ile `docker login ghcr.io` | L | ☑ |
| V-006 | `update-vito.ps1` ile bilgisayardaki Vito'yu yeni imaja geçir | L + C | ☑ |

## F1 — Hızlı kazanımlar
| No | Görev | Sahip | Durum |
|---|---|---|---|
| V-101 | Renkli (ANSI) log çıktısı | C | ☑ |
| V-102 | Cron ve worker hazır şablonları (Laravel); yeni worker'da numprocs varsayılanı 1 | C | ☑ |
| V-103 | PHP/Nginx limitleri sayfası — resmi Vito'da site ayarlarında zaten var (V-10), yapılmadı | C | – |
| V-104 | Rol/izin tablosu (proje davet penceresinde) | C | ☑ |
| V-105 | Yedek sağlık uyarıları: gecikme ve düzelme bildirimi (`backups:check-health`, 15 dk) | C | ☑ |

## F2 — Deploy ve .env
| No | Görev | Sahip | Durum |
|---|---|---|---|
| V-201 | Deploy geçmişi: kim deploy etti / geri aldı, tetikleyici (elle/API/webhook/workflow); log görüntüleme ve indirme zaten vardı | C | ☑ |
| V-202 | `.env` sürüm geçmişi (son 20, şifreli), geri alma, `APP_DEBUG` uyarısı; boş `.env` engellenmedi (V-12) | C | ☑ |
| V-203 | Site kurulum ilerlemesi — resmi Vito'da zaten var (V-13), yapılmadı | C | – |

## F3 — Tek tıkla Laravel
| No | Görev | Sahip | Durum |
|---|---|---|---|
| V-301 | "Laravel (hazır kurulum)" site türü | C | ☐ |

## F4 — Sunucu sayfaları
| No | Görev | Sahip | Durum |
|---|---|---|---|
| V-401 | İzleme: süreçler, disk, sunucu bilgisi, log boyutları | C | ☐ |
| V-402 | Yeniden başlatma ve güncelleme sayfaları | C | ☐ |
| V-403 | Güvenlik puanı ve sertleştirme kartları | C | ☐ |
| V-404 | Komut geçmişi (yalnızca admin) | C | ☐ |

## F5 — Gezinme ve tablolar
| No | Görev | Sahip | Durum |
|---|---|---|---|
| V-501 | Yeni ana menü | C | ☐ |
| V-502 | Sunucu menüsü grupları | C | ☐ |
| V-503 | Site sekmeleri | C | ☐ |
| V-504 | Tablo iyileştirmeleri | C | ☐ |
| V-505 | Genel bakış ekranı | C | ☐ |

## F6 — Projeler
| No | Görev | Sahip | Durum |
|---|---|---|---|
| V-601 | Davetler ve hızlı kullanıcı oluşturma | C | ☐ |

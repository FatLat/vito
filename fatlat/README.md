# FatLat Vito

Resmi [VitoDeploy](https://github.com/vitodeploy/vito) (4.x) üzerine FatLat'in eklemeleri. Vito, Latif'in Windows bilgisayarında Docker ile çalışır ve plaka-takip sunucusunu yönetir.

- `plan.md` — hangi özellikler, hangi aşamada
- `decisions.md` — kararlar ve gerekçeleri
- `tasks.md` — görevler ve durumları
- `update-vito.ps1` — bilgisayardaki Vito'yu en son imaja geçirir

## İmaj
`4.x`e push → resmi `tests` workflow'u → geçerse `fatlat-image` workflow'u arayüzü derler ve `ghcr.io/fatlat/vito:latest` (ve kısa commit etiketi) olarak yükler. Paket **gizlidir**.

## Bilgisayarda kullanım
Bir kez:
1. GitHub → Settings → Developer settings → Personal access tokens (classic) → yalnızca `read:packages` yetkili token.
2. PowerShell: `docker login ghcr.io -u <github-kullanıcı-adı>` → şifre olarak token.

Her güncellemede:
```powershell
git pull
powershell -ExecutionPolicy Bypass -File fatlat\update-vito.ps1
```
İmaj indirilemezse betik çalışan Vito'ya dokunmaz. Resmi imaja geri dönmek için: `-Image vitodeploy/vito:latest`.

Veriler (`vito_storage`, `vito_plugins` volume'leri) ve `%USERPROFILE%\.vito\vito.env` korunur. Betik mevcut konteynerdeki admin e-postasını kullanır; Vito yeni kullanıcı açmaz.

## Resmi Vito ile senkron
```bash
git remote add upstream https://github.com/vitodeploy/vito.git
git fetch upstream 4.x
git merge upstream/4.x
```
Çakışma çıkarsa FatLat değişiklikleri korunur; her özellik ayrı commit'te ve testlidir.

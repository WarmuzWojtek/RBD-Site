AUTOMATYCZNE ODŚWIEŻANIE LISTY WYDAŃ (YouTube -> videos.json)
==============================================================

Jak to działa:
- fetch-videos.php pobiera RSS kanału YouTube (bez klucza API) i zapisuje
  najnowsze wydania do videos.json (pomija Shorts).
- RSS zwraca tylko ~15 ostatnich wrzutek kanału, więc jeśli świeżych pełnych
  wydań jest mniej niż 8, brakującą resztę dobiera ze releases-archive.json
  (starsze, znane wydania — bez duplikatów po id z tymi z RSS).
- index.html przy każdym wczytaniu strony robi fetch("videos.json") i
  renderuje sekcję "Latest Releases" z tego pliku. Jeśli plik nie istnieje
  albo fetch się nie uda, wyświetla się zapasowa, wpisana na sztywno lista
  (RELEASES_FALLBACK w index.html).
- Artystów (sekcja "Artists") lista utworów NADAL aktualizujesz ręcznie w
  index.html (const ARTISTS) — kanał nie ma osobnych playlist per artysta,
  więc automatyczne przypisanie nowego klipu do konkretnego projektu nie
  byłoby wiarygodne.

Wdrożenie na hostido.pl:
1. Wgraj na serwer: index.html, fetch-videos.php, releases-archive.json
   (videos.json wgraj też, jako pierwsze dane — zostanie nadpisany przy
   pierwszym uruchomieniu crona).
2. W panelu hostido znajdź sekcję "Cron" / "Zadania cykliczne".
3. Dodaj nowe zadanie:
   - Częstotliwość: raz w tygodniu (np. w niedzielę o 03:00)
   - Komenda: php /home/TWOJA_NAZWA_KONTA/public_html/fetch-videos.php
     (dokładną ścieżkę do katalogu ze stroną podpowie panel hostido przy
     dodawaniu zadania cron)
4. Po zapisaniu poczekaj na pierwsze uruchomienie (albo wywołaj zadanie
   ręcznie z panelu, jeśli hostido na to pozwala) i sprawdź, czy
   videos.json zaktualizował się (pole "updated" w pliku).

Uwaga: jeśli plan hostingowy nie ma dostępu do crona lub do PHP CLI,
napisz — wtedy trzeba będzie znaleźć inne rozwiązanie (np. wywołanie
fetch-videos.php przez HTTP z zewnętrznego serwisu typu cron-job.org).

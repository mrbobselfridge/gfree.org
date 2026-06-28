# Operations Notes

## Slide Deck Import Dependencies

The Slide Deck Import tool runs in Laravel queue workers. The server and any local machine that needs to process decks must have these binaries available on the queue worker `PATH`:

- LibreOffice: `libreoffice` or `soffice`
- ImageMagick: `magick` or `convert`

Check availability:

```bash
command -v libreoffice || command -v soffice
command -v magick || command -v convert
```

On Ubuntu/Debian servers, install the expected packages with:

```bash
sudo apt update
sudo apt install -y libreoffice imagemagick ghostscript
```

For the production queue worker, confirm the service environment:

```bash
systemctl show gfree-queue.service -p Environment
```

If the service does not define a PATH, add a systemd override:

```bash
sudo systemctl edit gfree-queue.service
```

Use:

```ini
[Service]
Environment="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"
```

Then restart the queue worker:

```bash
sudo systemctl daemon-reload
sudo systemctl restart gfree-queue.service
sudo systemctl status gfree-queue.service
```

Any code change that adds or changes queue-worker system dependencies should call this out for both local development and the server.

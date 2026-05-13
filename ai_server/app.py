"""
Flask AI Server - MOCK VERSION.

Server ini mengembalikan hasil DUMMY untuk menguji integrasi end-to-end
sebelum model YOLOv8n (best.pt) tersedia.

Cara ganti ke model asli:
    1. Pastikan best.pt ada di ./model/best.pt
    2. Install dependency tambahan: pip install ultralytics opencv-python
    3. Rename file ini (atau backup), lalu rename app_yolo.py -> app.py
    4. Jalankan ulang: python app.py

Endpoint:
    POST /predict      - terima image (multipart/form-data), balas JSON hasil deteksi
    GET  /health       - healthcheck
    GET  /             - info server

Response /predict:
{
    "success":      true,
    "label":        "Canker",
    "confidence":   0.87,
    "image_base64": "<base64 citra dengan bounding box>",
    "bbox":         [x1, y1, x2, y2],
    "message":      "..."
}
"""

import base64
import io
import os
import random
from pathlib import Path

from flask import Flask, jsonify, request
from flask_cors import CORS
from PIL import Image, ImageDraw, ImageFont

# ---------------------------------------------------------------------------
# Konfigurasi
# ---------------------------------------------------------------------------
APP_PORT = int(os.environ.get("PORT", 5000))
APP_HOST = os.environ.get("HOST", "0.0.0.0")

# 6 kelas tetap (HARUS sama dengan tabel `penyakit` di MySQL).
CLASSES = ["Canker", "HLB", "Greasy Spot", "Melanose", "Sooty Mold", "Healthy"]

ALLOWED_EXT  = {"jpg", "jpeg", "png"}
MAX_FILESIZE = 5 * 1024 * 1024  # 5 MB

app = Flask(__name__)
CORS(app)


# ---------------------------------------------------------------------------
# Helper
# ---------------------------------------------------------------------------
def _allowed(filename: str) -> bool:
    return "." in filename and filename.rsplit(".", 1)[1].lower() in ALLOWED_EXT


def _mock_predict(img: Image.Image):
    """
    Mengembalikan (label, confidence, bbox_xyxy).
    bbox proporsional terhadap ukuran gambar input.
    """
    w, h = img.size
    label = random.choice(CLASSES)
    confidence = round(random.uniform(0.70, 0.98), 4)

    # Bounding box acak tapi masuk akal (di tengah)
    bw, bh = int(w * 0.5), int(h * 0.5)
    x1 = int(w * 0.25)
    y1 = int(h * 0.25)
    x2 = x1 + bw
    y2 = y1 + bh
    return label, confidence, (x1, y1, x2, y2)


def _draw_bbox(img: Image.Image, bbox, label: str, confidence: float) -> Image.Image:
    """Gambar bounding box + label di atas citra (in-place copy)."""
    out = img.convert("RGB").copy()
    draw = ImageDraw.Draw(out)

    x1, y1, x2, y2 = bbox
    color = (46, 125, 50) if label == "Healthy" else (211, 47, 47)

    # Kotak
    for i in range(4):  # garis tebal 4px
        draw.rectangle([x1 - i, y1 - i, x2 + i, y2 + i], outline=color)

    # Label
    text = f"{label} {confidence * 100:.1f}%"
    try:
        font = ImageFont.truetype("DejaVuSans-Bold.ttf", size=max(14, out.width // 30))
    except (OSError, IOError):
        font = ImageFont.load_default()

    # Background label
    try:
        tb = draw.textbbox((x1, y1), text, font=font)
        tw, th = tb[2] - tb[0], tb[3] - tb[1]
    except AttributeError:
        tw, th = draw.textsize(text, font=font)

    pad = 6
    bg_y1 = max(0, y1 - th - pad * 2)
    draw.rectangle([x1, bg_y1, x1 + tw + pad * 2, bg_y1 + th + pad * 2], fill=color)
    draw.text((x1 + pad, bg_y1 + pad), text, fill=(255, 255, 255), font=font)

    return out


def _to_base64(img: Image.Image, fmt: str = "JPEG") -> str:
    buf = io.BytesIO()
    img.save(buf, format=fmt, quality=90)
    return base64.b64encode(buf.getvalue()).decode("ascii")


# ---------------------------------------------------------------------------
# Routes
# ---------------------------------------------------------------------------
@app.get("/")
def index():
    return jsonify({
        "service": "Deteksi Penyakit Daun Jeruk - AI Server (MOCK)",
        "version": "1.0.0-mock",
        "mode":    "mock",
        "classes": CLASSES,
        "endpoints": ["GET /", "GET /health", "POST /predict"],
        "note":     "Ganti ke app_yolo.py setelah best.pt tersedia.",
    })


@app.get("/health")
def health():
    return jsonify({"status": "ok", "mode": "mock"})


@app.post("/predict")
def predict():
    # Validasi file
    if "image" not in request.files:
        return jsonify({"success": False, "message": "Field 'image' tidak ditemukan"}), 400

    f = request.files["image"]
    if not f or f.filename == "":
        return jsonify({"success": False, "message": "File kosong"}), 400
    if not _allowed(f.filename):
        return jsonify({"success": False, "message": "Format harus JPG/PNG"}), 400

    # Baca & validasi ukuran
    blob = f.read()
    if len(blob) > MAX_FILESIZE:
        return jsonify({"success": False, "message": "Ukuran file melebihi 5 MB"}), 400

    try:
        img = Image.open(io.BytesIO(blob))
        img.verify()  # verifikasi
        img = Image.open(io.BytesIO(blob))  # reload karena verify() close stream
    except Exception as e:
        return jsonify({"success": False, "message": f"Citra tidak valid: {e}"}), 400

    # Preprocess: resize ke 640x640 (seperti YOLOv8n) - untuk mock tetap pakai original
    # supaya bounding box tergambar di gambar asli agar lebih natural.
    label, confidence, bbox = _mock_predict(img)

    # Gambar bounding box
    out_img = _draw_bbox(img, bbox, label, confidence)
    img_b64 = _to_base64(out_img, fmt="JPEG")

    return jsonify({
        "success":      True,
        "label":        label,
        "confidence":   confidence,
        "bbox":         list(bbox),
        "image_base64": img_b64,
        "message":      "OK (mock response)",
    })


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
if __name__ == "__main__":
    print("=" * 60)
    print("AI Server (MOCK) - Deteksi Penyakit Daun Jeruk")
    print(f"Listening on http://{APP_HOST}:{APP_PORT}")
    print(f"Classes: {CLASSES}")
    print("=" * 60)
    app.run(host=APP_HOST, port=APP_PORT, debug=True)

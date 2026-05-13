"""
Flask AI Server - VERSI YOLOv8n ASLI.

Aktifkan file ini setelah file `best.pt` tersedia di ./model/best.pt.
Langkah:
    1. pip install -r requirements.txt   (uncomment baris ultralytics dkk)
    2. Taruh best.pt di ./model/best.pt
    3. Rename app.py -> app_mock.py, lalu rename app_yolo.py -> app.py
    4. python app.py

Endpoint sama persis dengan mock (agar PHP tidak perlu diubah).
"""

import base64
import io
import os

import cv2
import numpy as np
from flask import Flask, jsonify, request
from flask_cors import CORS
from PIL import Image
from ultralytics import YOLO

# ---------------------------------------------------------------------------
# Konfigurasi
# ---------------------------------------------------------------------------
APP_PORT   = int(os.environ.get("PORT", 5000))
APP_HOST   = os.environ.get("HOST", "0.0.0.0")
MODEL_PATH = os.environ.get("MODEL_PATH", "./model/best.pt")
IMG_SIZE   = 640
CONF_THRES = 0.25

# HARUS sesuai urutan training model.
# Jika urutan berbeda, sesuaikan di sini (atau pakai model.names langsung).
CLASSES = ["Canker", "HLB", "Greasy Spot", "Melanose", "Sooty Mold", "Healthy"]

ALLOWED_EXT  = {"jpg", "jpeg", "png"}
MAX_FILESIZE = 5 * 1024 * 1024

app = Flask(__name__)
CORS(app)

print(f"[AI] Memuat model dari {MODEL_PATH} ...")
model = YOLO(MODEL_PATH)
print(f"[AI] Model siap. Classes: {model.names}")


# ---------------------------------------------------------------------------
# Helper
# ---------------------------------------------------------------------------
def _allowed(filename: str) -> bool:
    return "." in filename and filename.rsplit(".", 1)[1].lower() in ALLOWED_EXT


def _to_base64(img_bgr: np.ndarray) -> str:
    ok, buf = cv2.imencode(".jpg", img_bgr, [cv2.IMWRITE_JPEG_QUALITY, 90])
    if not ok:
        return ""
    return base64.b64encode(buf.tobytes()).decode("ascii")


def _draw_box(img, xyxy, label, conf):
    x1, y1, x2, y2 = map(int, xyxy)
    color = (50, 125, 46) if label == "Healthy" else (47, 47, 211)  # BGR
    cv2.rectangle(img, (x1, y1), (x2, y2), color, 3)

    text = f"{label} {conf * 100:.1f}%"
    (tw, th), _ = cv2.getTextSize(text, cv2.FONT_HERSHEY_SIMPLEX, 0.7, 2)
    cv2.rectangle(img, (x1, y1 - th - 10), (x1 + tw + 10, y1), color, -1)
    cv2.putText(img, text, (x1 + 5, y1 - 6),
                cv2.FONT_HERSHEY_SIMPLEX, 0.7, (255, 255, 255), 2)


# ---------------------------------------------------------------------------
# Routes
# ---------------------------------------------------------------------------
@app.get("/")
def index():
    return jsonify({
        "service": "Deteksi Penyakit Daun Jeruk - AI Server (YOLOv8n)",
        "version": "1.0.0",
        "mode":    "production",
        "model":   MODEL_PATH,
        "classes": list(model.names.values()),
    })


@app.get("/health")
def health():
    return jsonify({"status": "ok", "mode": "production"})


@app.post("/predict")
def predict():
    if "image" not in request.files:
        return jsonify({"success": False, "message": "Field 'image' tidak ditemukan"}), 400

    f = request.files["image"]
    if not f or f.filename == "" or not _allowed(f.filename):
        return jsonify({"success": False, "message": "File tidak valid"}), 400

    blob = f.read()
    if len(blob) > MAX_FILESIZE:
        return jsonify({"success": False, "message": "Ukuran file melebihi 5 MB"}), 400

    # Decode ke numpy (BGR untuk OpenCV)
    arr = np.frombuffer(blob, dtype=np.uint8)
    img_bgr = cv2.imdecode(arr, cv2.IMREAD_COLOR)
    if img_bgr is None:
        return jsonify({"success": False, "message": "Citra tidak dapat dibaca"}), 400

    # Inferensi
    results = model.predict(img_bgr, imgsz=IMG_SIZE, conf=CONF_THRES, verbose=False)
    r = results[0]

    if r.boxes is None or len(r.boxes) == 0:
        # Tidak ada deteksi → default Healthy
        return jsonify({
            "success":      True,
            "label":        "Healthy",
            "confidence":   0.0,
            "bbox":         [0, 0, 0, 0],
            "image_base64": _to_base64(img_bgr),
            "message":      "Tidak ada objek terdeteksi (default: Healthy)",
        })

    # Ambil deteksi dengan confidence tertinggi
    confs = r.boxes.conf.cpu().numpy()
    idx   = int(np.argmax(confs))
    xyxy  = r.boxes.xyxy.cpu().numpy()[idx]
    cls_idx = int(r.boxes.cls.cpu().numpy()[idx])
    conf  = float(confs[idx])

    # Ambil label dari model.names (atau fallback ke CLASSES)
    label = model.names.get(cls_idx) if isinstance(model.names, dict) else model.names[cls_idx]
    if label not in CLASSES:
        # Jika nama di model tidak match, pakai index ke CLASSES
        if 0 <= cls_idx < len(CLASSES):
            label = CLASSES[cls_idx]

    # Gambar bounding box
    _draw_box(img_bgr, xyxy, label, conf)

    return jsonify({
        "success":      True,
        "label":        label,
        "confidence":   round(conf, 4),
        "bbox":         [int(v) for v in xyxy],
        "image_base64": _to_base64(img_bgr),
        "message":      "OK",
    })


# ---------------------------------------------------------------------------
if __name__ == "__main__":
    print("=" * 60)
    print("AI Server (YOLOv8n) - Deteksi Penyakit Daun Jeruk")
    print(f"Listening on http://{APP_HOST}:{APP_PORT}")
    print("=" * 60)
    app.run(host=APP_HOST, port=APP_PORT, debug=False)

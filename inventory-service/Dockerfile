FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /srv/inventory

COPY requirements.txt ./
RUN pip install --no-cache-dir -r requirements.txt

COPY app ./app
COPY inventory ./inventory
COPY grpc_client.py test_reservation.py demo_concurrent_reservation.py ./

CMD ["sh", "-c", "python -m app.seed && python -m app.migrate_constraints && python -m app.grpc_server"]

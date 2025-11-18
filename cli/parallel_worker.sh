#!/bin/bash
###############################################################################
# Gambio Language Generator - Parallel Worker Launcher
#
# Startet mehrere Worker-Prozesse parallel für schnellere Übersetzungen
#
# Usage:
#   ./parallel_worker.sh [num_workers] [jobs_per_worker]
#
# Examples:
#   ./parallel_worker.sh           # 3 Worker, 10 Jobs pro Worker
#   ./parallel_worker.sh 5         # 5 Worker, 10 Jobs pro Worker
#   ./parallel_worker.sh 5 20      # 5 Worker, 20 Jobs pro Worker
#
# @author Christian Mittenzwei
# @version 1.0.0
###############################################################################

# Konfiguration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKER_SCRIPT="$SCRIPT_DIR/worker.php"
NUM_WORKERS=${1:-3}              # Standard: 3 parallele Worker
JOBS_PER_WORKER=${2:-10}         # Standard: 10 Jobs pro Worker
LOG_DIR="/tmp/glg_workers"

# Validierung
if [ ! -f "$WORKER_SCRIPT" ]; then
    echo "ERROR: Worker script not found: $WORKER_SCRIPT"
    exit 1
fi

if ! [[ "$NUM_WORKERS" =~ ^[0-9]+$ ]] || [ "$NUM_WORKERS" -lt 1 ] || [ "$NUM_WORKERS" -gt 10 ]; then
    echo "ERROR: Invalid number of workers (must be 1-10): $NUM_WORKERS"
    exit 1
fi

# Erstelle Log-Verzeichnis
mkdir -p "$LOG_DIR"

echo "=============================================="
echo "GLG Parallel Worker Launcher"
echo "=============================================="
echo "Workers:         $NUM_WORKERS"
echo "Jobs per worker: $JOBS_PER_WORKER"
echo "Log directory:   $LOG_DIR"
echo "=============================================="

# Finde PHP-Binary
PHP_BIN=$(which php)
if [ -z "$PHP_BIN" ]; then
    echo "ERROR: PHP binary not found"
    exit 1
fi

echo "PHP binary:      $PHP_BIN"
echo ""

# Starte Worker-Prozesse parallel
PIDS=()
for i in $(seq 1 $NUM_WORKERS); do
    LOG_FILE="$LOG_DIR/worker_${i}.log"
    echo "[Worker $i] Starting... (log: $LOG_FILE)"

    # Starte Worker im Hintergrund
    $PHP_BIN "$WORKER_SCRIPT" $JOBS_PER_WORKER > "$LOG_FILE" 2>&1 &

    PID=$!
    PIDS+=($PID)
    echo "[Worker $i] Started with PID: $PID"

    # Kurze Pause zwischen Worker-Starts (verhindert Race Conditions)
    sleep 0.5
done

echo ""
echo "All workers started!"
echo "=============================================="
echo "Monitoring workers (Ctrl+C to stop)..."
echo ""

# Warte auf alle Worker-Prozesse
COMPLETED=0
TOTAL=$NUM_WORKERS

while [ $COMPLETED -lt $TOTAL ]; do
    RUNNING=0

    for i in "${!PIDS[@]}"; do
        PID=${PIDS[$i]}

        if kill -0 $PID 2>/dev/null; then
            RUNNING=$((RUNNING + 1))
        fi
    done

    COMPLETED=$((TOTAL - RUNNING))

    echo -ne "\rWorkers: $RUNNING running, $COMPLETED completed"

    # Warte 2 Sekunden bevor nächster Check
    sleep 2
done

echo ""
echo ""
echo "=============================================="
echo "All workers completed!"
echo "=============================================="

# Zeige Logs
echo ""
echo "Worker Logs:"
echo "------------"
for i in $(seq 1 $NUM_WORKERS); do
    LOG_FILE="$LOG_DIR/worker_${i}.log"

    if [ -f "$LOG_FILE" ]; then
        echo ""
        echo "=== Worker $i ==="
        tail -n 5 "$LOG_FILE"
    fi
done

echo ""
echo "Full logs available in: $LOG_DIR"
echo "=============================================="

exit 0

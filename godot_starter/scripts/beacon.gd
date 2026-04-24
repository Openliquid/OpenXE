extends Area2D
class_name ControlBeacon

signal captured(team_id: int)

@export var radius: float = 120.0
@export var hold_time_required: float = 20.0

var controlling_team: int = -1
var hold_progress: float = 0.0

@onready var ring: Line2D = $Ring
@onready var label: Label = $Label

func _ready() -> void:
    monitoring = true
    ring.width = 2.0
    _refresh_visual()

func update_control(units: Array[RTSUnit], delta: float) -> void:
    var p := 0
    var e := 0
    for u in units:
        if not is_instance_valid(u):
            continue
        if global_position.distance_to(u.global_position) <= radius:
            if u.team_id == 0:
                p += 1
            elif u.team_id == 1:
                e += 1

    var next_team := -1
    if p > e and p > 0:
        next_team = 0
    elif e > p and e > 0:
        next_team = 1

    if next_team == -1:
        hold_progress = max(hold_progress - delta * 0.5, 0.0)
        if hold_progress == 0.0:
            controlling_team = -1
    elif controlling_team == next_team:
        hold_progress = min(hold_progress + delta, hold_time_required)
    else:
        controlling_team = next_team
        hold_progress = min(delta, hold_time_required)

    if hold_progress >= hold_time_required:
        captured.emit(controlling_team)
        hold_progress = 0.0

    _refresh_visual()

func _refresh_visual() -> void:
    var pct := int((hold_progress / hold_time_required) * 100.0)
    if controlling_team == 0:
        ring.default_color = Color(0.3, 0.7, 1.0, 1.0)
        label.text = "Beacon: Spieler %d%%" % pct
    elif controlling_team == 1:
        ring.default_color = Color(1.0, 0.35, 0.3, 1.0)
        label.text = "Beacon: Gegner %d%%" % pct
    else:
        ring.default_color = Color(0.9, 0.9, 0.9, 1.0)
        label.text = "Beacon: neutral"

#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import re
import unicodedata
from pathlib import Path
import pandas as pd

# -----------------------------
# Normalisation / helpers
# -----------------------------

def strip_md_decor(s: str) -> str:
    s = s.strip()
    s = re.sub(r"^[#\s>*`_]+", "", s)
    s = s.replace("**", "").replace("__", "")
    return s.strip()

def norm_key(s: str) -> str:
    s = (s or "").strip()
    s = unicodedata.normalize("NFD", s)
    s = "".join(ch for ch in s if unicodedata.category(ch) != "Mn")
    s = s.lower().replace("’", "'")
    s = re.sub(r"\s+", " ", s)
    return s

def split_outside_parens(s: str, sep: str = ","):
    """Split par sep uniquement hors parenthèses."""
    out, buf, depth = [], [], 0
    for ch in s:
        if ch == "(":
            depth += 1
        elif ch == ")":
            depth = max(0, depth - 1)
        if ch == sep and depth == 0:
            out.append("".join(buf).strip())
            buf = []
        else:
            buf.append(ch)
    if buf:
        out.append("".join(buf).strip())
    return [x for x in out if x]

def clean_captain_markers(name: str):
    """Retire (c)/(cap)/© etc."""
    t = (name or "").strip()
    t = t.replace("©", "")
    t = re.sub(r"\((c|cap|C)\)", "", t, flags=re.I).strip()
    t = re.sub(r"\s+", " ", t).strip()
    return t

def clean_player_name(raw: str) -> str:
    """
    Nettoie un token joueur:
      - retire numéros maillot en tête (ex: '22 - Adams')
      - retire minutes '61'' en fin si elles trainent
      - retire marqueurs capitaine
    """
    t = (raw or "").strip().strip(".")
    t = re.sub(r"^\s*\d{1,2}\s*-\s*", "", t)               # "22 - Adams"
    t = re.sub(r"\b\d{1,3}\s*[’']\s*$", "", t).strip()     # "61'"
    t = clean_captain_markers(t)
    t = re.sub(r"\s+", " ", t).strip()
    return t

TEAM_LINE_RE = re.compile(r"^([^:]{2,})\s*:\s*(.+)$", re.U)

# Coach marker (ligne joueurs)
COACH_MARK_RE = re.compile(r"\b(Entraineurs?|Entraîneurs?|Entraineur|Entraîneur)\b\s*:\s*", re.I)

# -----------------------------
# Parsing des matchs (headers)
# -----------------------------

HEADER_RE = re.compile(r"^\(?\s*(\d+)\s*\)\s*(.+)$")
SCORE_RE  = re.compile(r"^(.*?)\s+(\d+)\s+(.+?)\s+(\d+)\s*$", re.U)

def iter_md_matches(md_text: str):
    lines = [strip_md_decor(l) for l in md_text.splitlines()]
    matches = []
    cur = None

    def flush():
        nonlocal cur
        if cur:
            matches.append(cur)
            cur = None

    for raw in lines:
        line = raw.strip()
        if not line:
            continue

        m = HEADER_RE.match(line)
        if m:
            num = int(m.group(1))
            rest = m.group(2).strip()
            sm = SCORE_RE.match(rest)
            if sm:
                flush()
                cur = {
                    "match_no": num,
                    "team_a": sm.group(1).strip(),
                    "score_a": int(sm.group(2)),
                    "team_b": sm.group(3).strip(),
                    "score_b": int(sm.group(4)),
                    "raw_lines": []
                }
                continue

        if cur:
            cur["raw_lines"].append(line)

    flush()
    return matches

# -----------------------------
# Extraction adversaire + joueurs + changements + coach
# -----------------------------

def extract_adv_team_name(match) -> str:
    a = match["team_a"]
    b = match["team_b"]
    a_is_dz = norm_key(a) == norm_key("algérie") or "alger" in norm_key(a)
    b_is_dz = norm_key(b) == norm_key("algérie") or "alger" in norm_key(b)
    if a_is_dz and not b_is_dz:
        return b
    if b_is_dz and not a_is_dz:
        return a
    return ""  # fallback

def split_coach_names(payload: str):
    """
    Transforme "Nom1. Nom2 et Nom3" => ["Nom1","Nom2","Nom3"]
    (on n’utilise ici que 2 colonnes: entraineur + 1 assistant)
    """
    p = (payload or "").strip().strip(".")
    p = re.sub(r"\s+et\s+", ",", p, flags=re.I)
    p = p.replace(";", ",")
    chunks = []
    for part in p.split("."):
        part = part.strip()
        if part:
            chunks.extend([x.strip() for x in part.split(",") if x.strip()])

    # dedup ordre
    out, seen = [], set()
    for c in chunks:
        k = norm_key(c)
        if k and k not in seen:
            out.append(c)
            seen.add(k)
    return out

def split_payload_players_vs_coach(payload: str):
    """
    La consigne: coach = "tout ce qui vient après Entraineur(s):" en fin de ligne.
    Donc on coupe la ligne en:
      players_part, coach_part
    """
    m = COACH_MARK_RE.search(payload)
    if not m:
        return payload.strip(), ""
    players_part = payload[:m.start()].strip().strip(",").strip()
    coach_part = payload[m.end():].strip()
    return players_part, coach_part

def parse_adv_players_and_subs(players_payload: str):
    """
    Retourne:
      - players20: adv_j1..adv_j20 (11 titulaires + entrants)
      - subs_out10: adv_chang1..adv_chang10 (sortants + minute si dispo)
    Règles:
      - Un joueur entre parenthèses = entrant
      - Sortant = joueur juste avant
      - Minute si dispo dans parenthèse: (Entrant, 61') => "Sortant 61'"
    """
    payload = players_payload.strip().strip(".")
    tokens = split_outside_parens(payload, ",")

    starters = []
    entrants = []
    subs_out = []
    last_out = ""

    for tok in tokens:
        t = tok.strip().strip(".")
        if not t:
            continue

        # (Entrant, 61') ou (Entrant)
        if re.match(r"^\(.+\)$", t):
            inner = t.strip("() ").strip()

            minute = ""
            mmin = re.search(r"(.*?)(?:,|\s)\s*(\d{1,3})\s*[’']\s*$", inner)
            if mmin:
                inner_name = mmin.group(1).strip()
                minute = mmin.group(2).strip()
            else:
                inner_name = inner.strip()

            in_name = clean_player_name(inner_name)
            if in_name:
                entrants.append(in_name)

            if last_out:
                out_val = last_out
                if minute:
                    out_val = f"{last_out} {minute}'"
                subs_out.append(out_val)
            continue

        # OUT (IN, 61') ou OUT (IN)
        msub = re.match(r"^(.*?)\s*\(([^)]+)\)\s*$", t)
        if msub:
            out_raw = msub.group(1).strip()
            in_raw = msub.group(2).strip()

            out_name = clean_player_name(out_raw)
            if out_name:
                starters.append(out_name)
                last_out = out_name

            minute = ""
            mmin = re.search(r"(.*?)(?:,|\s)\s*(\d{1,3})\s*[’']\s*$", in_raw)
            if mmin:
                in_name_raw = mmin.group(1).strip()
                minute = mmin.group(2).strip()
            else:
                in_name_raw = in_raw.strip()

            in_name = clean_player_name(in_name_raw)
            if in_name:
                entrants.append(in_name)

            if out_name:
                out_val = out_name
                if minute:
                    out_val = f"{out_name} {minute}'"
                subs_out.append(out_val)
            continue

        # Joueur simple
        name = clean_player_name(t)
        if name:
            starters.append(name)
            last_out = name

    def dedup_keep(seq):
        seen = set()
        out = []
        for x in seq:
            k = norm_key(x)
            if k and k not in seen:
                out.append(x)
                seen.add(k)
        return out

    starters = dedup_keep(starters)
    entrants = dedup_keep(entrants)
    subs_out = dedup_keep(subs_out)

    players20 = starters[:11] + entrants[:9]
    players20 += [""] * (20 - len(players20))

    subs_out10 = subs_out[:10]
    subs_out10 += [""] * (10 - len(subs_out10))

    return players20, subs_out10

def extract_adv_from_match(match):
    """
    Retourne:
      adv_team, players20, subs_out10, entraineur_adv, assistant_adv
    """
    adv_team = extract_adv_team_name(match)
    ctx = None
    pending_pick_first_non_dz = (adv_team == "")

    for l in match["raw_lines"]:
        m = TEAM_LINE_RE.match(l)
        if not m:
            continue
        team = m.group(1).strip()
        payload = m.group(2).strip()

        if re.match(r"^Alg[ée]rie$", team, flags=re.I):
            ctx = "dz"
            continue

        # Ligne adverse (explicite)
        if adv_team and norm_key(team) == norm_key(adv_team):
            players_part, coach_part = split_payload_players_vs_coach(payload)
            players20, subs_out10 = parse_adv_players_and_subs(players_part)

            ent, asst = "", ""
            if coach_part:
                names = split_coach_names(coach_part)
                if len(names) >= 1:
                    ent = names[0]
                if len(names) >= 2:
                    asst = names[1]

            return team, players20, subs_out10, ent, asst

        # Fallback: 1ère équipe non-Algérie après Algérie
        if pending_pick_first_non_dz and ctx == "dz":
            players_part, coach_part = split_payload_players_vs_coach(payload)
            players20, subs_out10 = parse_adv_players_and_subs(players_part)

            ent, asst = "", ""
            if coach_part:
                names = split_coach_names(coach_part)
                if len(names) >= 1:
                    ent = names[0]
                if len(names) >= 2:
                    asst = names[1]

            return team, players20, subs_out10, ent, asst

    return adv_team, [""] * 20, [""] * 10, "", ""

# -----------------------------
# Main: export xlsx/csv
# -----------------------------

def main():
    import argparse
    ap = argparse.ArgumentParser()
    ap.add_argument("--md", required=True, help="Matches EN 1963 -15 janv.2026.utf8.md")
    ap.add_argument("--out", default="matches_adv_joueurs_changements_coach.xlsx", help="Output XLSX")
    args = ap.parse_args()

    md_text = Path(args.md).read_text(encoding="utf-8")
    matches = iter_md_matches(md_text)

    rows = []
    for m in matches:
        adv_team, players20, subs_out10, ent, asst = extract_adv_from_match(m)
        row = {
            "N° du match": m["match_no"],
            "Equipe adverse": adv_team,
            "Entraineur adv": ent,
            "Entraineur assistant adv": asst
        }
        for i in range(20):
            row[f"adv_j{i+1}"] = players20[i]
        for i in range(10):
            row[f"adv_chang{i+1}"] = subs_out10[i]
        rows.append(row)

    df = pd.DataFrame(rows).drop_duplicates(subset=["N° du match"]).sort_values("N° du match")

    # force 1..679
    full = pd.DataFrame({"N° du match": list(range(1, 680))})
    out_df = full.merge(df, on="N° du match", how="left")

    out_xlsx = Path(args.out)
    out_csv = out_xlsx.with_suffix(".csv")

    out_df.to_excel(out_xlsx, index=False)
    out_df.to_csv(out_csv, index=False, encoding="utf-8")

    print("OK:", out_xlsx, "et", out_csv)
    print("Matchs détectés dans MD:", len(df))
    print("Lignes 1..679:", len(out_df))
    print("Entraineur adv renseigné:", out_df["Entraineur adv"].notna().sum())

if __name__ == "__main__":
    main()

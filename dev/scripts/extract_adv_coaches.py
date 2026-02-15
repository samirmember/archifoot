#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import re
import unicodedata
from pathlib import Path
import pandas as pd

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

# Header: (xxx) TeamA scoreA TeamB scoreB
HEADER_RE = re.compile(r"^\(?\s*(\d+)\s*\)\s*(.+)$")
SCORE_RE  = re.compile(r"^(.*?)\s+(\d+)\s+(.+?)\s+(\d+)\s*$", re.U)

COACH_MARK_RE = re.compile(r"\b(Entraineurs?|Entraîneurs?|Entraineur|Entraîneur|Sélectionneur)\b\s*:", re.I)

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

def split_coach_names(payload: str):
    """
    Transforme "Nom1. Nom2 et Nom3" => ["Nom1","Nom2","Nom3"]
    """
    p = (payload or "").strip().strip(".")
    p = re.sub(r"\s+et\s+", ",", p, flags=re.I)
    p = p.replace(";", ",")
    chunks = []
    for part in p.split("."):
        part = part.strip()
        if part:
            chunks.extend([x.strip() for x in part.split(",") if x.strip()])

    # dédoublonne en gardant l'ordre
    out, seen = [], set()
    for c in chunks:
        k = norm_key(c)
        if k and k not in seen:
            out.append(c)
            seen.add(k)
    return out

def extract_coach_clause_from_line(line: str):
    """
    Extrait la partie après "Entraineur(s) :" dans UNE ligne.
    Retourne liste de noms.
    """
    m = re.search(r":\s*(.+)$", line)
    if not m:
        return []
    return split_coach_names(m.group(1))

def extract_adv_coaches(match):
    """
    Retourne (entraineur_adv, assistant_adv) pour l'équipe adverse uniquement.
    On capture les coachs:
      - sur la même ligne que le listing adverse
      - ou sur une ligne suivante, tant que le contexte est "adv"
    """
    team_a = match["team_a"]
    team_b = match["team_b"]

    a_is_dz = (norm_key(team_a) == norm_key("algérie")) or ("alger" in norm_key(team_a))
    b_is_dz = (norm_key(team_b) == norm_key("algérie")) or ("alger" in norm_key(team_b))

    # équipe adverse = l'autre côté de l'Algérie
    adv_team = team_b if a_is_dz else (team_a if b_is_dz else "")

    ctx = None  # None / "dz" / "adv"
    adv_ent = ""
    adv_asst = ""

    for l in match["raw_lines"]:
        # Switch contexte quand on voit un listing d'équipe
        if re.match(r"^Alg[ée]rie\s*:", l, flags=re.I):
            ctx = "dz"
        elif adv_team and re.match(rf"^{re.escape(adv_team)}\s*:", l):
            ctx = "adv"
        elif re.match(r"^[^:]{2,}:\s", l) and not re.match(r"^Alg[ée]rie\s*:", l, flags=re.I):
            # fallback si libellé adversaire inattendu : première équipe non-Algérie après avoir vu Algérie
            if ctx == "dz" and not adv_team:
                ctx = "adv"
                adv_team = l.split(":", 1)[0].strip()

        # Si la ligne courante contient un coach (même si c'est la ligne de listing)
        if COACH_MARK_RE.search(l):
            if ctx != "adv":
                continue
            # Si coach est "à la fin du listing", on prend uniquement la clause après Entraineur(s):
            # On coupe avant le mot clé, puis on reprend depuis le mot clé.
            # Exemple: "Bulgarie : ..., Entraineur : X."
            coach_part = re.split(COACH_MARK_RE, l, maxsplit=1)
            # re.split avec groupe retourne: [avant, motcle, apres...]
            if len(coach_part) >= 3:
                payload = coach_part[2]
                names = split_coach_names(payload)
            else:
                names = extract_coach_clause_from_line(l)

            if names and not adv_ent:
                adv_ent = names[0]
            if len(names) >= 2 and not adv_asst:
                adv_asst = names[1]
            # Si >2 noms, on ignore les suivants (tu veux 3 colonnes uniquement)

    return adv_ent, adv_asst


def main():
    import argparse
    ap = argparse.ArgumentParser()
    ap.add_argument("--md", required=True, help="Matches EN 1963 -15 janv.2026.utf8.md")
    ap.add_argument("--out", default="coachs_adv_1_679.xlsx", help="Output XLSX")
    args = ap.parse_args()

    md_text = Path(args.md).read_text(encoding="utf-8", errors="strict")
    matches = iter_md_matches(md_text)

    rows = []
    for m in matches:
        ent, asst = extract_adv_coaches(m)
        rows.append({
            "N° du match": m["match_no"],
            "Entraineur adv": ent,
            "Entraineur assistant adv": asst
        })

    df = pd.DataFrame(rows).drop_duplicates(subset=["N° du match"]).sort_values("N° du match")

    # force 1..679
    full = pd.DataFrame({"N° du match": list(range(1, 680))})
    out_df = full.merge(df, on="N° du match", how="left")

    out_xlsx = args.out
    out_csv  = Path(out_xlsx).with_suffix(".csv")
    out_df.to_excel(out_xlsx, index=False)
    out_df.to_csv(out_csv, index=False, encoding="utf-8")

    print("OK:", out_xlsx, "et", out_csv)
    print("Matchs trouvés dans MD:", len(df))
    print("Entraineur adv renseigné:", out_df["Entraineur adv"].notna().sum())
    print("Assistant adv renseigné:", out_df["Entraineur assistant adv"].notna().sum())

if __name__ == "__main__":
    main()

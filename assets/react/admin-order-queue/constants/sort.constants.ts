export const ASC = "ASC" as const;
export const DESC = "DESC" as const;

export type SortOrder = typeof ASC | typeof DESC;

-- Update habit_user color_index based on habit_id mapping
UPDATE habit_user 
SET color_index = CASE habit_id
    WHEN 1 THEN '#3B82F6'  -- blue (unknown habit)
    WHEN 2 THEN '#6B7280'  -- gray (Sleep)
    WHEN 3 THEN '#F97316'  -- orange (Learning)
    WHEN 4 THEN '#8B5CF6'  -- purple (Social)
    WHEN 5 THEN '#10B981'  -- green (Meditation - mental health)
    WHEN 6 THEN '#10B981'  -- green (Physical Activity - exercise)
    WHEN 7 THEN '#EC4899'  -- pink (Entertainment)
    WHEN 8 THEN '#6B7280'  -- gray (Projects)
    WHEN 9 THEN '#10B981'  -- green (Walking - exercise)
    WHEN 10 THEN '#F97316' -- orange (Japanese - learning)
    WHEN 11 THEN '#F97316' -- orange (German - learning)
    WHEN 12 THEN '#8B5CF6' -- purple (Family - social)
    WHEN 13 THEN '#8B5CF6' -- purple (SO - social)
    WHEN 14 THEN '#10B981' -- green (Strength Training - exercise)
    WHEN 15 THEN '#10B981' -- green (Stretching - exercise)
    WHEN 16 THEN '#10B981' -- green (Jump Rope - exercise)
    WHEN 17 THEN '#F97316' -- orange (Farsi - learning)
    WHEN 18 THEN '#8B5CF6' -- purple (Friends - social)
    WHEN 19 THEN '#10B981' -- green (Journal - mental wellness)
    WHEN 20 THEN '#0EA5E9' -- sky (Drink Water)
    WHEN 21 THEN '#10B981' -- green (Mental Wellness)
    WHEN 22 THEN '#6B7280' -- gray (Security - projects)
    ELSE '#FFFFFF'         -- default white for any other habit_id
END
WHERE habit_id IN (1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22);